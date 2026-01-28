<?php

namespace App\Services;

use App\Models\AppUser;
use App\Models\LiveStream;
use App\Models\LiveStreamAccessToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class LiveStreamPlaybackService
{
    public function tokenTtlSeconds(): int
    {
        return (int) config('services.live_stream.token_ttl_seconds', 300);
    }

    /**
     * Create a short-lived playback token (stored hashed).
     *
     * @return array{token: string, expires_at: \Illuminate\Support\Carbon}
     */
    public function issueToken(AppUser $user, LiveStream $liveStream, Request $request): array
    {
        $token = bin2hex(random_bytes(32)); // 64 chars
        $hash = hash('sha256', $token);
        $expiresAt = now()->addSeconds($this->tokenTtlSeconds());

        LiveStreamAccessToken::create([
            'user_id' => $user->id,
            'live_stream_id' => $liveStream->id,
            'token_hash' => $hash,
            'expires_at' => $expiresAt,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255) ?: null,
        ]);

        return [
            'token' => $token,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Validate playback token and return token record (or null).
     * Caches the result to reduce DB load (critical for high concurrency).
     */
    public function validateToken(string $token, LiveStream $liveStream): ?LiveStreamAccessToken
    {
        $hash = hash('sha256', $token);
        $cacheKey = "live_token_{$hash}";

        // Try to get ID from cache first
        $tokenId = Cache::get($cacheKey);

        if ($tokenId === 'invalid') {
            return null;
        }

        if ($tokenId) {
            // fast retrieval by ID
            $record = LiveStreamAccessToken::find($tokenId);
        } else {
            $record = LiveStreamAccessToken::where('token_hash', $hash)->first();
        }

        if (!$record) {
            Cache::put($cacheKey, 'invalid', 60); // Cache negative result
            return null;
        }

        // Cache positive result for 60 seconds
        Cache::put($cacheKey, $record->id, 60);

        if ((int) $record->live_stream_id !== (int) $liveStream->id) {
            return null;
        }

        if (!$record->isValid()) {
            return null;
        }

        return $record;
    }

    /**
     * Check if the user can watch this live stream.
     * Cached for performance (1 minute).
     */
    public function userCanWatch(AppUser $user, LiveStream $liveStream): bool
    {
        $cacheKey = "user_{$user->id}_can_watch_stream_{$liveStream->id}";

        return Cache::remember($cacheKey, 60, function () use ($user, $liveStream) {
            return $this->resolveUserCanWatch($user, $liveStream);
        });
    }

    protected function resolveUserCanWatch(AppUser $user, LiveStream $liveStream): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (!$user->is_active) {
            return false;
        }

        if (!$liveStream->is_active) {
            return false;
        }

        $hasActiveBaseSubscription = $user->subscription && $user->subscription->isActive();
        if (!$hasActiveBaseSubscription) {
            return false;
        }

        // If stream is tied to a specific package, require that package (unless user has manual live_access override)
        if ($liveStream->live_stream_package_id) {
            if ($user->live_access === true) {
                return true;
            }

            return $user->hasActiveLiveStreamSubscription((int) $liveStream->live_stream_package_id);
        }

        $accessType = $liveStream->access_type ?? 'all_subscribers';
        if ($accessType === 'live_subscribers_only') {
            $hasLiveAccess = ($user->live_access === true) || $user->hasActiveLiveStreamSubscription();
            return $hasLiveAccess;
        }

        return true;
    }

    /**
     * Mark token as used and optionally extend expiration.
     * Throttled: updates DB at most once per minute per token to prevent hammering.
     */
    public function touchToken(LiveStreamAccessToken $token, bool $extendExpiry = false): void
    {
        $throttleKey = "live_token_touch_{$token->id}";

        // If recently updated, skip DB write
        if (Cache::has($throttleKey)) {
            return;
        }

        try {
            $update = [
                'last_used_at' => now(),
            ];

            if ($extendExpiry) {
                $update['expires_at'] = now()->addSeconds($this->tokenTtlSeconds());
            }

            $token->update($update);

            // Lock updates for this token for 60 seconds
            Cache::put($throttleKey, true, 60);

        } catch (\Throwable $e) {
            Log::warning('Failed to touch live stream token', [
                'token_id' => $token->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

