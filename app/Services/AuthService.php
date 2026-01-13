<?php

namespace App\Services;

use App\Models\AppUser;
use App\Models\LoginLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthService
{
    public function __construct(
        protected RadiusSyncService $radiusSyncService
    ) {}
    /**
     * Attempt to login user.
     * 
     * @return array{success: bool, user: ?AppUser, error: ?string}
     */
    public function login(string $username, string $password, string $ipAddress, ?string $userAgent = null): array
    {
        $user = AppUser::where('username', $username)->first();

        if (!$user) {
            $this->logLoginAttempt($username, $ipAddress, $userAgent, false, 'User not found');
            return [
                'success' => false,
                'user' => null,
                'error' => 'invalid_credentials',
            ];
        }

        if (!$user->is_active) {
            $this->logLoginAttempt($username, $ipAddress, $userAgent, false, 'Account is inactive', $user->id);
            return [
                'success' => false,
                'user' => null,
                'error' => 'account_disabled',
            ];
        }

        if (!Hash::check($password, $user->password)) {
            $this->logLoginAttempt($username, $ipAddress, $userAgent, false, 'Invalid password', $user->id);
            return [
                'success' => false,
                'user' => null,
                'error' => 'invalid_credentials',
            ];
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        // Log successful login
        $this->logLoginAttempt($username, $ipAddress, $userAgent, true, null, $user->id);

        return [
            'success' => true,
            'user' => $user,
            'error' => null,
        ];
    }

    /**
     * Register a new user.
     *
     * @throws \Exception If user already exists or doesn't exist in Radius
     */
    public function register(array $data): AppUser
    {
        // Check if user already exists in our application
        // Note: This is a double-check since RegisterRequest already validates this,
        // but we keep it for security and to provide a clear error message
        $existingUser = AppUser::where('username', $data['username'])->first();
        if ($existingUser) {
            throw new \Exception('اسم المستخدم مستخدم بالفعل. يرجى استخدام اسم مستخدم آخر أو تسجيل الدخول إذا كان لديك حساب.');
        }

        // Check if user exists in Radius
        $userExistsResult = $this->radiusSyncService->userExistsInRadius($data['username']);
        
        if ($userExistsResult === 'connection_error') {
            throw new \Exception('فشل الاتصال بنظام Radius. يرجى المحاولة مرة أخرى لاحقاً أو الاتصال بالدعم الفني.');
        }
        
        if ($userExistsResult === false) {
            throw new \Exception('اسم المستخدم غير موجود في نظام Radius. يرجى التحقق من اسم المستخدم.');
        }

        // Get user data from Radius
        $radiusData = $this->radiusSyncService->getUserDataFromRadius($data['username']);

        if (!$radiusData) {
            throw new \Exception('فشل في جلب بيانات المستخدم من نظام Radius. يرجى المحاولة مرة أخرى أو الاتصال بالدعم الفني.');
        }

        // Use phone from Radius if not provided in registration
        $phone = $data['phone'] ?? $radiusData['mobile'] ?? null;

        // Get firstname from Radius
        $firstname = $radiusData['firstname'] ?? null;

        // Create user account with data from Radius
        $user = AppUser::create([
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
            'firstname' => $firstname,
            'phone' => $phone,
            'email' => $data['email'] ?? null,
            'language' => $data['language'] ?? 'ar',
            'is_active' => true,
            'live_access' => false,
            'role' => 0, // Regular user
        ]);

        // Create user subscription with data from Radius
        $user->subscription()->create([
            'radius_username' => $data['username'],
            'expiration_at' => $radiusData['expiration_at'] ?? null,
            'balance' => $radiusData['balance'] ?? null,
            'data_limit' => $radiusData['data_limit'] ?? null,
            'data_used' => $radiusData['data_used'] ?? null,
            'plan_name' => $radiusData['plan_name'] ?? null,
            'auto_renew' => $radiusData['auto_renew'] ?? false,
            'firstname' => $radiusData['firstname'] ?? null,
            'mobile' => $radiusData['mobile'] ?? null,
            'is_active_radius' => $radiusData['is_active_radius'] ?? null,
            'is_online' => $radiusData['is_online'] ?? null,
            'download_kbps' => $radiusData['download_kbps'] ?? null,
            'upload_kbps' => $radiusData['upload_kbps'] ?? null,
            'download_mbps' => $radiusData['download_mbps'] ?? null,
            'upload_mbps' => $radiusData['upload_mbps'] ?? null,
            'download_MB' => $radiusData['download_MB'] ?? null,
            'upload_MB' => $radiusData['upload_MB'] ?? null,
            'total_MB' => $radiusData['total_MB'] ?? null,
            'raw_data' => $radiusData['raw_data'] ?? null,
            'last_synced_at' => now(),
            'sync_status' => 0, // Success
        ]);

        return $user;
    }

    /**
     * Log login attempt.
     */
    protected function logLoginAttempt(
        string $username,
        string $ipAddress,
        ?string $userAgent,
        bool $success,
        ?string $failureReason = null,
        ?int $userId = null
    ): void {
        try {
            LoginLog::create([
                'user_id' => $userId,
                'username' => $username,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'device_type' => $this->detectDeviceType($userAgent),
                'status' => $success ? 1 : 0,
                'failure_reason' => $failureReason,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log login attempt', [
                'error' => $e->getMessage(),
                'username' => $username,
            ]);
        }
    }

    /**
     * Detect device type from user agent.
     */
    protected function detectDeviceType(?string $userAgent): ?string
    {
        if (!$userAgent) {
            return null;
        }

        $userAgent = strtolower($userAgent);

        if (str_contains($userAgent, 'mobile') || str_contains($userAgent, 'android') || str_contains($userAgent, 'iphone')) {
            return 'mobile';
        }

        if (str_contains($userAgent, 'tablet') || str_contains($userAgent, 'ipad')) {
            return 'tablet';
        }

        return 'desktop';
    }
}

