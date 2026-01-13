<?php

namespace App\Services;

use App\Models\AppUser;
use App\Models\UserSubscription;
use App\Models\SyncLog;
use App\Enums\SyncStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RadiusSyncService
{
    protected string $radiusApiUrl;
    protected string $radiusApiKey;

    protected ?string $renewEndpoint;

    public function __construct()
    {
        $this->radiusApiUrl = config('services.radius.api_url', 'http://38.156.75.137:3031');
        $this->radiusApiKey = config('services.radius.api_key', 'APP2025M');
        $this->renewEndpoint = config('services.radius.renew_endpoint');
    }

    /**
     * Sync user subscription from Radius.
     */
    public function syncUserSubscription(AppUser $user): bool
    {
        try {
            $response = $this->fetchUserDataFromRadius($user->username);

            if (!$response) {
                $this->logSync($user->id, 'user', SyncStatus::FAILED, 'Failed to fetch data from Radius', 0);
                return false;
            }

            DB::transaction(function () use ($user, $response) {
                UserSubscription::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'radius_username' => $user->username,
                        'expiration_at' => $response['expiration_at'] ?? null,
                        'balance' => $response['balance'] ?? null,
                        'data_limit' => $response['data_limit'] ?? null,
                        'data_used' => $response['data_used'] ?? null,
                        'plan_name' => $response['plan_name'] ?? null,
                        'auto_renew' => $response['auto_renew'] ?? false,
                        'firstname' => $response['firstname'] ?? null,
                        'mobile' => $response['mobile'] ?? null,
                        'is_active_radius' => $response['is_active_radius'] ?? null,
                        'is_online' => $response['is_online'] ?? null,
                        'download_kbps' => $response['download_kbps'] ?? null,
                        'upload_kbps' => $response['upload_kbps'] ?? null,
                        'download_mbps' => $response['download_mbps'] ?? null,
                        'upload_mbps' => $response['upload_mbps'] ?? null,
                        'download_MB' => $response['download_MB'] ?? null,
                        'upload_MB' => $response['upload_MB'] ?? null,
                        'total_MB' => $response['total_MB'] ?? null,
                        'raw_data' => $response['raw_data'] ?? null,
                        'last_synced_at' => now(),
                        'sync_status' => SyncStatus::SUCCESS->value,
                    ]
                );
            });

            $this->logSync($user->id, 'user', SyncStatus::SUCCESS, null, 1);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to sync user subscription', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            $this->logSync($user->id, 'user', SyncStatus::FAILED, $e->getMessage(), 0);
            return false;
        }
    }

    /**
     * Sync all users subscriptions.
     */
    public function syncAllUsers(): int
    {
        $users = AppUser::where('is_active', true)->get();
        $syncedCount = 0;

        foreach ($users as $user) {
            if ($this->syncUserSubscription($user)) {
                $syncedCount++;
            }
        }

        $this->logSync(null, 'all', SyncStatus::SUCCESS, null, $syncedCount);
        return $syncedCount;
    }

    /**
     * Check if user exists in Radius.
     *
     * @return bool|string Returns true if user exists, false if not found, or error message string if connection failed
     */
    public function userExistsInRadius(string $username): bool|string
    {
        try {
            $url = rtrim($this->radiusApiUrl, '/') . '/radiusmanager/USERS/dash/test.php';

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->radiusApiKey,
                'Accept' => 'application/json',
            ])->timeout(30)->get($url, [
                'input' => $username,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Check if response indicates success and user exists
                if (is_array($data) && isset($data['status']) && $data['status'] === 'success') {
                    return true;
                }

                // User not found in Radius
                return false;
            }

            // HTTP request failed
            Log::error('Radius API request failed', [
                'username' => $username,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return 'connection_error';
        } catch (\Exception $e) {
            Log::error('Failed to check user existence in Radius', [
                'username' => $username,
                'error' => $e->getMessage(),
            ]);

            return 'connection_error';
        }
    }

    /**
     * Get user data directly from Radius API without saving to database.
     * Useful for real-time data fetching.
     *
     * Returns all fields from Radius including:
     * - Basic info (expiration, balance, plan_name)
     * - Speed info (download/upload kbps, mbps)
     * - Usage info (download/upload/total MB)
     * - Status info (is_active, is_online)
     * - Raw data (complete response)
     */
    public function getUserDataFromRadius(string $username): ?array
    {
        try {
            $url = rtrim($this->radiusApiUrl, '/') . '/radiusmanager/USERS/dash/test.php';

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->radiusApiKey,
                'Accept' => 'application/json',
            ])->timeout(30)->get($url, [
                'input' => $username,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Check if response indicates success
                if (isset($data['status']) && $data['status'] !== 'success') {
                    return null;
                }

                // Return normalized data with all fields
                if (is_array($data)) {
                    return $this->normalizeRadiusResponse($data);
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get user data from Radius', [
                'username' => $username,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get available services/plans list directly from Radius API.
     *
     * Expected Radius response format (example):
     * {
     *   "status": "success",
     *   "count": 12,
     *   "services": [
     *     {
     *       "service_id": 7,
     *       "service_name": "10M-PPP",
     *       "price": "30.00",
     *       "currency": "SYP",
     *       "speed": {
     *         "download_kbps": 10485760,
     *         "upload_kbps": 104857600,
     *         "download_mbps": 10485.8,
     *         "upload_mbps": 104857.6
     *       },
     *       "unlimited": true
     *     }
     *   ]
     * }
     */
    public function getServicesFromRadius(): ?array
    {
        try {
            $url = rtrim($this->radiusApiUrl, '/') . '/radiusmanager/USERS/dash/get_services.php';

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->radiusApiKey,
                'Accept' => 'application/json',
            ])->timeout(30)->get($url);

            if ($response->successful()) {
                $data = $response->json();

                if (is_array($data) && isset($data['status']) && $data['status'] === 'success') {
                    // Normalize plan names in services array
                    if (isset($data['services']) && is_array($data['services'])) {
                        foreach ($data['services'] as &$service) {
                            if (isset($service['service_name'])) {
                                $service['service_name'] = $this->normalizePlanName($service['service_name']);
                            }
                        }
                        unset($service); // Break reference
                    }

                    return $data;
                }

                Log::warning('Radius services API returned non-success status', [
                    'status' => $data['status'] ?? 'unknown',
                    'response' => $response->body(),
                ]);
            } else {
                Log::error('Radius services API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get services from Radius', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Fetch user data from Radius API.
     */
    protected function fetchUserDataFromRadius(string $username): ?array
    {
        try {
            $url = rtrim($this->radiusApiUrl, '/') . '/radiusmanager/USERS/dash/test.php';

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->radiusApiKey,
                'Accept' => 'application/json',
            ])->timeout(30)->get($url, [
                'input' => $username,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Check if response indicates success
                if (isset($data['status']) && $data['status'] !== 'success') {
                    Log::warning('Radius API returned non-success status', [
                        'username' => $username,
                        'status' => $data['status'] ?? 'unknown',
                        'response' => $response->body(),
                    ]);
                    return null;
                }

                // Handle different response formats
                if (is_array($data)) {
                    // Normalize and return
                    return $this->normalizeRadiusResponse($data);
                }

                // If response is string, try to decode it
                if (is_string($data)) {
                    $decoded = json_decode($data, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        return $this->normalizeRadiusResponse($decoded);
                    }
                }

                Log::warning('Unexpected response format from Radius API', [
                    'username' => $username,
                    'response' => $response->body(),
                ]);

                return null;
            }

            Log::error('Radius API request failed', [
                'username' => $username,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to fetch data from Radius API', [
                'username' => $username,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Normalize Radius API response to our expected format.
     *
     * Expected Radius response format:
     * {
     *   "status": "success",
     *   "username": "001",
     *   "firstname": "الشيخ احمد الطحبش",
     *   "mobile": "",
     *   "service": "2M-PPP",
     *   "price": "8.000000",
     *   "expiration": "2026-01-02 10:00:00",
     *   "active": true,
     *   "online": false,
     *   "speed": {
     *     "download_kbps": 2097152,
     *     "upload_kbps": 20971520,
     *     "download_mbps": 2097.2,
     *     "upload_mbps": 20971.5
     *   },
     *   "usage": {
     *     "download_MB": 8371.59,
     *     "upload_MB": 67316.8,
     *     "total_MB": 75688.39
     *   }
     * }
     */
    protected function normalizeRadiusResponse(array $data): array
    {
        // Extract usage data
        $usage = $data['usage'] ?? [];
        $totalMB = $usage['total_MB'] ?? null;
        $downloadMB = $usage['download_MB'] ?? null;
        $uploadMB = $usage['upload_MB'] ?? null;

        // Convert total_MB to bytes for data_used
        $dataUsed = null;
        if ($totalMB !== null && is_numeric($totalMB)) {
            $dataUsed = (int) ($totalMB * 1024 * 1024); // Convert MB to bytes
        }

        // Extract speed data
        $speed = $data['speed'] ?? [];

        // Map Radius response fields to our format
        return [
            'expiration_at' => $this->parseDate($data['expiration'] ?? $data['expiration_at'] ?? $data['expires_at'] ?? null),
            'balance' => $this->parseDecimal($data['price'] ?? $data['balance'] ?? $data['credit'] ?? $data['amount'] ?? null),
            'data_limit' => $this->parseBytes($data['data_limit'] ?? $data['limit'] ?? $data['dataLimit'] ?? null),
            'data_used' => $dataUsed ?? $this->parseBytes($data['data_used'] ?? $data['used'] ?? $data['dataUsed'] ?? null),
            'plan_name' => $this->normalizePlanName(
                $data['service'] ?? $data['plan_name'] ?? $data['plan'] ?? $data['planName'] ?? null
            ),
            'auto_renew' => isset($data['auto_renew']) ? (bool) $data['auto_renew'] : (isset($data['autoRenew']) ? (bool) $data['autoRenew'] : false),

            // Additional fields
            'firstname' => $data['firstname'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'is_active_radius' => isset($data['active']) ? (bool) $data['active'] : null,
            'is_online' => isset($data['online']) ? (bool) $data['online'] : null,

            // Speed fields
            'download_kbps' => isset($speed['download_kbps']) ? (int) $speed['download_kbps'] : null,
            'upload_kbps' => isset($speed['upload_kbps']) ? (int) $speed['upload_kbps'] : null,
            'download_mbps' => isset($speed['download_mbps']) ? $this->parseDecimal($speed['download_mbps']) : null,
            'upload_mbps' => isset($speed['upload_mbps']) ? $this->parseDecimal($speed['upload_mbps']) : null,

            // Usage fields
            'download_MB' => $downloadMB ? $this->parseDecimal($downloadMB) : null,
            'upload_MB' => $uploadMB ? $this->parseDecimal($uploadMB) : null,
            'total_MB' => $totalMB ? $this->parseDecimal($totalMB) : null,

            // Store raw data for reference
            'raw_data' => $data,
        ];
    }

    /**
     * Normalize plan name by removing anything starting from "-P".
     * Example: "10M-PPP" -> "10M"
     */
    protected function normalizePlanName(?string $name): ?string
    {
        if (!$name) {
            return null;
        }

        $pos = strpos($name, '-P');

        if ($pos === false) {
            return $name;
        }

        return substr($name, 0, $pos);
    }

    /**
     * Parse date from various formats.
     */
    protected function parseDate($date): ?string
    {
        if (!$date) {
            return null;
        }

        try {
            // Try to parse as timestamp
            if (is_numeric($date)) {
                return date('Y-m-d H:i:s', $date);
            }

            // Try to parse as date string
            $parsed = \Carbon\Carbon::parse($date);
            return $parsed->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            Log::warning('Failed to parse date from Radius', [
                'date' => $date,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Parse decimal value.
     */
    protected function parseDecimal($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    /**
     * Parse bytes from various formats (KB, MB, GB, etc.).
     * Supports decimal values (e.g., 75688.39 MB).
     */
    protected function parseBytes($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        // If already in bytes (integer or float)
        if (is_numeric($value) && !is_string($value)) {
            return (int) $value;
        }

        // If string with unit
        if (is_string($value)) {
            $value = trim($value);

            // Check for common units
            if (preg_match('/^([\d.]+)\s*(KB|MB|GB|TB)$/i', $value, $matches)) {
                $number = (float) $matches[1];
                $unit = strtoupper($matches[2]);

                return match ($unit) {
                    'KB' => (int) ($number * 1024),
                    'MB' => (int) ($number * 1024 * 1024),
                    'GB' => (int) ($number * 1024 * 1024 * 1024),
                    'TB' => (int) ($number * 1024 * 1024 * 1024 * 1024),
                    default => (int) $value,
                };
            }

            // Try to parse as number
            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return (int) $value;
    }

    /**
     * Get plan_id from plan_name by fetching services from Radius.
     *
     * @param string $planName The plan/service name
     * @return int|null The plan_id if found, null otherwise
     */
    protected function getPlanIdFromPlanName(string $planName): ?int
    {
        try {
            $services = $this->getServicesFromRadius();
            
            if (!$services || !isset($services['services']) || !is_array($services['services'])) {
                return null;
            }

            // Search for matching service by name
            foreach ($services['services'] as $service) {
                $serviceName = $service['service_name'] ?? null;
                if ($serviceName && (
                    $serviceName === $planName ||
                    str_contains($serviceName, $planName) ||
                    str_contains($planName, $serviceName)
                )) {
                    return $service['service_id'] ?? null;
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::warning('Failed to get plan_id from plan_name', [
                'plan_name' => $planName,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Renew subscription in Radius by adding months.
     *
     * This method uses the new renew_subscription.php endpoint which requires:
     * - username: The username in Radius
     * - plan_id: The plan ID (will be fetched from plan_name if provided)
     * - months: Number of months to add (supports decimal values like 0.5)
     * - paid_status: Payment status (default: "paid")
     *
     * @param string $username The username in Radius
     * @param float|int $months Number of months to add (supports decimal like 0.5)
     * @param string|null $planName Optional plan/service name to set (will be converted to plan_id)
     * @param string $paidStatus Payment status (default: "paid")
     * @return bool True if successful, false otherwise
     */
    public function renewSubscription(string $username, float|int $months, ?string $planName = null, string $paidStatus = 'paid'): bool
    {
        try {
            // Use the new renew_subscription.php endpoint
            $endpoint = $this->renewEndpoint ?? '/radiusmanager/USERS/dash/renew_subscription.php';
            $url = rtrim($this->radiusApiUrl, '/') . $endpoint;

            // Build payload with required fields
            $payload = [
                'username' => $username,
                'months' => (float) $months, // Support decimal months (e.g., 0.5)
                'paid_status' => $paidStatus,
            ];

            // Get plan_id from plan_name if provided
            $planId = null;
            if ($planName) {
                $planId = $this->getPlanIdFromPlanName($planName);
                
                if ($planId === null) {
                    Log::warning('Could not find plan_id for plan_name, attempting renewal without plan_id', [
                        'username' => $username,
                        'plan_name' => $planName,
                    ]);
                } else {
                    $payload['plan_id'] = $planId;
                    Log::info('Found plan_id for plan_name', [
                        'plan_name' => $planName,
                        'plan_id' => $planId,
                    ]);
                }
            }

            Log::info('Attempting to renew subscription in Radius', [
                'username' => $username,
                'months' => $months,
                'plan_name' => $planName,
                'plan_id' => $planId,
                'paid_status' => $paidStatus,
                'endpoint' => $endpoint,
                'url' => $url,
                'payload' => $payload,
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->radiusApiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();

                // Check if response indicates success
                if (isset($data['status']) && $data['status'] === 'success') {
                    Log::info('Subscription renewed successfully in Radius', [
                        'username' => $username,
                        'months' => $months,
                        'plan_name' => $planName,
                        'plan_id' => $planId,
                        'response' => $data,
                    ]);
                    return true;
                } else {
                    $errorMessage = $data['message'] ?? ($data['status'] ?? 'unknown');
                    Log::warning('Radius renewal API returned non-success status', [
                        'username' => $username,
                        'endpoint' => $endpoint,
                        'response' => $data,
                        'error' => $errorMessage,
                    ]);
                    return false;
                }
            } else {
                $errorBody = $response->body();
                Log::error('Radius renewal API request failed', [
                    'username' => $username,
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $errorBody,
                ]);

                // If unauthorized, log specific error
                if ($response->status() === 401 || $response->status() === 403) {
                    Log::error('Radius API authentication failed - check API key', [
                        'username' => $username,
                        'api_key' => substr($this->radiusApiKey, 0, 5) . '...',
                    ]);
                }

                return false;
            }
        } catch (\Exception $e) {
            Log::error('Failed to renew subscription in Radius', [
                'username' => $username,
                'months' => $months,
                'plan' => $planName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Update subscription expiration date in Radius.
     * Alternative method if renew endpoint doesn't work.
     *
     * @param string $username The username in Radius
     * @param int $months Number of months to add
     * @param string|null $planName Optional plan/service name to set
     * @return bool True if successful, false otherwise
     */
    protected function updateSubscriptionExpiration(string $username, int $months, ?string $planName = null): bool
    {
        try {
            // Get current subscription data
            $currentData = $this->getUserDataFromRadius($username);

            if (!$currentData) {
                Log::error('Cannot update expiration: user data not found in Radius', [
                    'username' => $username,
                ]);
                return false;
            }

            // Calculate new expiration date
            $currentExpiration = $currentData['expiration_at']
                ? \Carbon\Carbon::parse($currentData['expiration_at'])
                : now();

            $newExpiration = $currentExpiration->copy()->addMonths($months);

            // Build payload
            $payload = [
                'username' => $username,
                'expiration' => $newExpiration->format('Y-m-d H:i:s'),
            ];

            // Add plan/service name if provided
            if ($planName) {
                $payload['service'] = $planName;
                $payload['plan'] = $planName;
                $payload['plan_name'] = $planName;
            }

            // Try to update expiration
            $url = rtrim($this->radiusApiUrl, '/') . '/radiusmanager/USERS/update.php';

            Log::info('Attempting to update subscription expiration in Radius', [
                'username' => $username,
                'months' => $months,
                'plan' => $planName,
                'current_expiration' => $currentExpiration->format('Y-m-d H:i:s'),
                'new_expiration' => $newExpiration->format('Y-m-d H:i:s'),
                'url' => $url,
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->radiusApiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['status']) && $data['status'] === 'success') {
                    Log::info('Subscription expiration updated successfully in Radius', [
                        'username' => $username,
                        'new_expiration' => $newExpiration->format('Y-m-d H:i:s'),
                        'plan' => $planName,
                    ]);
                    return true;
                } else {
                    Log::warning('Radius update API returned non-success status', [
                        'username' => $username,
                        'response' => $data,
                    ]);
                }
            } else {
                Log::warning('Radius update API request failed', [
                    'username' => $username,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to update subscription expiration in Radius', [
                'username' => $username,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Log sync operation.
     */
    protected function logSync(?int $userId, string $syncType, SyncStatus $status, ?string $errorMessage, int $recordsSynced): void
    {
        try {
            SyncLog::create([
                'user_id' => $userId,
                'sync_type' => $syncType,
                'status' => $status->value,
                'error_message' => $errorMessage,
                'records_synced' => $recordsSynced,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log sync operation', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}

