<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\DeviceToken;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Exception\Messaging\InvalidMessage;

class FirebaseMessagingService
{
    protected $messaging;
    protected $serverKey;

    public function __construct()
    {
        $credentialsPath = config('services.firebase.credentials_path');
        $projectId = config('services.firebase.project_id');
        $this->serverKey = config('services.firebase.server_key');

        // Resolve path - handle both relative and absolute paths
        if ($credentialsPath) {
            // If path is relative (starts with 'storage/' or doesn't start with '/')
            if (!str_starts_with($credentialsPath, '/') && !str_starts_with($credentialsPath, DIRECTORY_SEPARATOR)) {
                // Try to resolve relative path
                if (str_starts_with($credentialsPath, 'storage/')) {
                    $credentialsPath = base_path($credentialsPath);
                } else {
                    // Assume it's relative to storage/app
                    $credentialsPath = storage_path('app/' . ltrim($credentialsPath, 'app/'));
                }
            }
        } else {
            // Default path
            $credentialsPath = storage_path('app/firebase/service-account-key.json');
        }

        if ($credentialsPath && file_exists($credentialsPath)) {
            try {
                $factory = (new Factory)->withServiceAccount($credentialsPath);
                $this->messaging = $factory->createMessaging();
                Log::info('Firebase SDK initialized successfully', [
                    'path' => $credentialsPath,
                    'project_id' => $projectId,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to initialize Firebase SDK', [
                    'path' => $credentialsPath,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        } else {
            Log::warning('Firebase credentials file not found', [
                'config_path' => config('services.firebase.credentials_path'),
                'resolved_path' => $credentialsPath,
                'file_exists' => $credentialsPath ? file_exists($credentialsPath) : false,
                'storage_path' => storage_path('app/firebase/service-account-key.json'),
                'storage_path_exists' => file_exists(storage_path('app/firebase/service-account-key.json')),
            ]);
        }
    }

    /**
     * Send notification to a single device token.
     *
     * @param string $token FCM device token
     * @param array $notificationData Notification data (title, body, etc.)
     * @param array $data Additional data payload
     * @return bool
     */
    public function sendToDevice(string $token, array $notificationData, array $data = []): bool
    {
        try {
            Log::debug('FirebaseMessagingService: Attempting to send to device', [
                'token_preview' => substr($token, 0, 20) . '...',
                'has_messaging' => !is_null($this->messaging),
            ]);

            if (!$this->messaging) {
                Log::warning('FirebaseMessagingService: SDK not available, using HTTP fallback', [
                    'token_preview' => substr($token, 0, 20) . '...',
                ]);
                return $this->sendViaHttp($token, $notificationData, $data);
            }

            $notification = Notification::create(
                $notificationData['title'] ?? '',
                $notificationData['body'] ?? ''
            );

            $message = CloudMessage::withTarget('token', $token)
                ->withNotification($notification)
                ->withData($data);

            // Add Android config
            if (isset($notificationData['android'])) {
                $androidConfig = AndroidConfig::fromArray($notificationData['android']);
                $message = $message->withAndroidConfig($androidConfig);
            }

            // Add iOS config
            if (isset($notificationData['apns'])) {
                $apnsConfig = ApnsConfig::fromArray($notificationData['apns']);
                $message = $message->withApnsConfig($apnsConfig);
            }

            $result = $this->messaging->send($message);

            Log::info('FCM notification sent successfully', [
                'token' => substr($token, 0, 20) . '...',
                'message_id' => $result,
            ]);

            return true;
        } catch (NotFound $e) {
            Log::warning('FCM token not found, deleting from database', [
                'token' => substr($token, 0, 20) . '...',
            ]);
            $this->deleteInvalidToken($token);
            return false;
        } catch (InvalidMessage $e) {
            if (str_contains($e->getMessage(), 'Registration token is not valid') ||
                str_contains($e->getMessage(), 'not a valid FCM registration token')) {
                Log::warning('FCM token invalid, deleting from database', [
                    'token' => substr($token, 0, 20) . '...',
                ]);
                $this->deleteInvalidToken($token);
            }
            return false;
        } catch (\Exception $e) {
            Log::error('Failed to send FCM notification', [
                'token' => substr($token, 0, 20) . '...',
                'error' => $e->getMessage(),
            ]);

            // Fallback to HTTP method
            return $this->sendViaHttp($token, $notificationData, $data);
        }
    }

    /**
     * Delete invalid token from database.
     */
    protected function deleteInvalidToken(string $token): void
    {
        try {
            DeviceToken::where('device_token', $token)->delete();
        } catch (\Exception $e) {
            Log::error('Failed to delete invalid token', [
                'token' => substr($token, 0, 20) . '...',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notification to multiple device tokens.
     *
     * @param array $tokens Array of FCM device tokens
     * @param array $notificationData Notification data
     * @param array $data Additional data payload
     * @return array Array of results (true/false for each token)
     */
    public function sendToDevices(array $tokens, array $notificationData, array $data = []): array
    {
        Log::info('FirebaseMessagingService: Starting batch send', [
            'tokens_count' => count($tokens),
            'title' => $notificationData['title'] ?? 'N/A',
        ]);

        $results = [];

        foreach ($tokens as $token) {
            $results[$token] = $this->sendToDevice($token, $notificationData, $data);
        }

        $successCount = count(array_filter($results, fn($r) => $r === true));
        $failCount = count($results) - $successCount;

        Log::info('FirebaseMessagingService: Batch send completed', [
            'total' => count($tokens),
            'success' => $successCount,
            'failed' => $failCount,
        ]);

        return $results;
    }

    /**
     * Send notification via HTTP API (fallback method).
     * Note: This uses Legacy API which is deprecated. SDK method is preferred.
     *
     * @param string $token FCM device token
     * @param array $notificationData Notification data
     * @param array $data Additional data payload
     * @return bool
     */
    protected function sendViaHttp(string $token, array $notificationData, array $data = []): bool
    {
        if (!$this->serverKey) {
            Log::warning('FCM Server Key not configured - HTTP fallback unavailable. Using SDK only.');
            return false;
        }

        try {
            $payload = [
                'to' => $token,
                'notification' => [
                    'title' => $notificationData['title'] ?? '',
                    'body' => $notificationData['body'] ?? '',
                    'sound' => $notificationData['sound'] ?? 'default',
                    'badge' => $notificationData['badge'] ?? null,
                    'icon' => $notificationData['icon'] ?? null,
                ],
                'data' => $data,
                'priority' => 'high',
            ];

            // Remove null values
            $payload = array_filter($payload, fn($value) => $value !== null);
            $payload['notification'] = array_filter($payload['notification'], fn($value) => $value !== null);

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', $payload);

            if ($response->successful()) {
                $responseData = $response->json();

                if (isset($responseData['success']) && $responseData['success'] == 1) {
                    Log::info('FCM notification sent via HTTP', [
                        'token' => substr($token, 0, 20) . '...',
                    ]);
                    return true;
                } else {
                    Log::warning('FCM HTTP request failed', [
                        'token' => substr($token, 0, 20) . '...',
                        'response' => $responseData,
                    ]);

                    // Check for invalid token errors in legacy API
                    if (isset($responseData['results'][0]['error'])) {
                        $error = $responseData['results'][0]['error'];
                        if (in_array($error, ['NotRegistered', 'InvalidRegistration'])) {
                            Log::info('Removing invalid token based on HTTP response', ['token' => substr($token, 0, 20) . '...']);
                            $this->deleteInvalidToken($token);
                        }
                    }
                }
            } else {
                Log::error('FCM HTTP request error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to send FCM notification via HTTP', [
                'token' => substr($token, 0, 20) . '...',
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Validate FCM token format.
     *
     * @param string $token
     * @return bool
     */
    public function validateToken(string $token): bool
    {
        // FCM tokens are typically 152-163 characters long
        // and contain alphanumeric characters and some special characters
        return strlen($token) >= 100 && strlen($token) <= 200;
    }
}

