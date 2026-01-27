<?php

namespace App\Http\Controllers;

use App\Models\LiveStream;
use App\Services\LiveStreamPlaybackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LiveStreamPlaybackController extends Controller
{
    public function __construct(
        protected LiveStreamPlaybackService $playbackService
    ) {}

    /**
     * Create a playback URL (auth required).
     */
    public function create(Request $request, LiveStream $liveStream): JsonResponse
    {
        $user = $request->user();

        if (!$this->playbackService->userCanWatch($user, $liveStream)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لمشاهدة هذا البث',
            ], 403);
        }

        $issued = $this->playbackService->issueToken($user, $liveStream, $request);
        $url = url("/api/live-streams/{$liveStream->id}/secure") . '?token=' . $issued['token'];

        return response()->json([
            'success' => true,
            'data' => [
                'playback_url' => $url,
                'expires_at' => $issued['expires_at']->toIso8601String(),
            ],
        ]);
    }

    /**
     * Secure playback proxy endpoint (HLS supported).
     *
     * - Public route (no Authorization header required)
     * - Requires ?token=...
     * - Optional ?u= (encrypted absolute upstream URL) used for segments/sub-playlists/keys
     */
    public function secure(Request $request, LiveStream $liveStream)
    {
        $tokenValue = (string) $request->query('token', '');
        if ($tokenValue === '') {
            return response()->json([
                'success' => false,
                'message' => 'token is required',
            ], 401);
        }

        $token = $this->playbackService->validateToken($tokenValue, $liveStream);
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'توكن غير صالح أو منتهي',
            ], 403);
        }

        $user = $token->user;
        if (!$user || !$this->playbackService->userCanWatch($user, $liveStream)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لمشاهدة هذا البث',
            ], 403);
        }

        $upstreamUrl = $liveStream->stream_url;
        $encryptedUpstream = $request->query('u');
        if ($encryptedUpstream) {
            try {
                $upstreamUrl = Crypt::decryptString((string) $encryptedUpstream);
            } catch (\Throwable) {
                return response()->json([
                    'success' => false,
                    'message' => 'رابط غير صالح',
                ], 400);
            }
        }

        // Safety: only allow fetching from the same host as the main stream_url
        $baseHost = parse_url((string) $liveStream->stream_url, PHP_URL_HOST);
        $targetHost = parse_url((string) $upstreamUrl, PHP_URL_HOST);
        if ($baseHost && $targetHost && $baseHost !== $targetHost) {
            return response()->json([
                'success' => false,
                'message' => 'رابط غير مسموح',
            ], 403);
        }

        $range = $request->header('Range');
        $http = Http::withOptions([
            'timeout' => 20,
            'connect_timeout' => 10,
            'stream' => true,
        ]);
        if ($range) {
            $http = $http->withHeaders(['Range' => $range]);
        }

        $resp = $http->get($upstreamUrl);
        if (!$resp->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'فشل تحميل البث',
            ], 502);
        }

        $contentType = $resp->header('Content-Type') ?? 'application/octet-stream';
        $isM3u8 = str_contains(strtolower($contentType), 'application/vnd.apple.mpegurl')
            || str_contains(strtolower($contentType), 'application/x-mpegurl')
            || str_ends_with(strtolower(parse_url($upstreamUrl, PHP_URL_PATH) ?? ''), '.m3u8');

        if ($isM3u8) {
            $rewritten = $this->rewriteM3u8((string) $resp->body(), $upstreamUrl, $liveStream->id, $tokenValue);

            // Extend expiry for playlists (keeps playback alive without client refresh)
            $this->playbackService->touchToken($token, true);

            return response($rewritten, 200, [
                'Content-Type' => 'application/vnd.apple.mpegurl',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
            ]);
        }

        // For segments/keys: stream binary (small enough for now; avoids exposing upstream)
        $this->playbackService->touchToken($token, false);

        $psr = $resp->toPsrResponse();
        $status = $psr->getStatusCode();

        $headers = [
            'Content-Type' => $contentType,
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ];
        if ($range && $resp->header('Content-Range')) {
            $headers['Content-Range'] = $resp->header('Content-Range');
        }
        if ($resp->header('Accept-Ranges')) {
            $headers['Accept-Ranges'] = $resp->header('Accept-Ranges');
        }

        return new StreamedResponse(function () use ($psr) {
            $body = $psr->getBody();
            while (!$body->eof()) {
                echo $body->read(1024 * 64);
                flush();
            }
        }, $status, $headers);
    }

    protected function rewriteM3u8(string $content, string $baseUrl, int $liveStreamId, string $token): string
    {
        $lines = preg_split("/\r\n|\n|\r/", $content) ?: [];
        $out = [];
        $proxyBase = url("/api/live-streams/{$liveStreamId}/secure") . '?token=' . $token;

        foreach ($lines as $line) {
            $trim = trim($line);

            if ($trim === '') {
                $out[] = $line;
                continue;
            }

            // Rewrite key URI inside EXT-X-KEY
            if (str_starts_with($trim, '#EXT-X-KEY')) {
                $out[] = preg_replace_callback('/URI="([^"]+)"/', function ($m) use ($baseUrl, $proxyBase) {
                    $abs = $this->resolveUrl($baseUrl, $m[1]);
                    $enc = urlencode(Crypt::encryptString($abs));
                    return 'URI="' . $proxyBase . '&u=' . $enc . '"';
                }, $line) ?? $line;
                continue;
            }

            // Comments stay as-is
            if (str_starts_with($trim, '#')) {
                $out[] = $line;
                continue;
            }

            // URI line (segment or nested playlist)
            $abs = $this->resolveUrl($baseUrl, $trim);
            $enc = urlencode(Crypt::encryptString($abs));
            $out[] = $proxyBase . '&u=' . $enc;
        }

        return implode("\n", $out);
    }

    protected function resolveUrl(string $baseUrl, string $ref): string
    {
        // Absolute
        if (preg_match('/^https?:\/\//i', $ref)) {
            return $ref;
        }

        // Scheme-relative
        if (str_starts_with($ref, '//')) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';
            return $scheme . ':' . $ref;
        }

        $parts = parse_url($baseUrl);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = $parts['path'] ?? '/';

        // Base directory
        $dir = str_contains($path, '/') ? substr($path, 0, strrpos($path, '/') + 1) : '/';

        // Root-relative
        if (str_starts_with($ref, '/')) {
            return "{$scheme}://{$host}{$port}{$ref}";
        }

        return "{$scheme}://{$host}{$port}{$dir}{$ref}";
    }
}

