<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\HasApiTokens;

class AppUser extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'app_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'password',
        'firstname',
        'phone',
        'email',
        'is_active',
        'live_access',
        'role',
        'device_token',
        'language',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'live_access' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Get all payment requests for the user.
     */
    public function paymentRequests(): HasMany
    {
        return $this->hasMany(PaymentRequest::class, 'user_id');
    }

    /**
     * Get all maintenance requests for the user.
     */
    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class, 'user_id');
    }

    /**
     * Get maintenance requests assigned to the user (as admin/accountant).
     */
    public function assignedMaintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class, 'assigned_to');
    }

    /**
     * Get all revenues for the user.
     */
    public function revenues(): HasMany
    {
        return $this->hasMany(Revenue::class, 'user_id');
    }

    /**
     * Get all notifications for the user.
     */
    public function notifications(): BelongsToMany
    {
        return $this->belongsToMany(Notification::class, 'notification_user', 'user_id', 'notification_id')
            ->withPivot('is_read', 'is_sent', 'sent_at', 'read_at', 'send_error')
            ->withTimestamps();
    }

    /**
     * Get the user's subscription.
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(UserSubscription::class, 'user_id');
    }

    /**
     * Live stream subscriptions (packages).
     */
    public function liveStreamSubscriptions(): HasMany
    {
        return $this->hasMany(LiveStreamSubscription::class, 'user_id');
    }

    public function hasActiveLiveStreamSubscription(?int $packageId = null): bool
    {
        $query = $this->liveStreamSubscriptions()
            ->where('status', 'active')
            ->where('expires_at', '>', now());

        if ($packageId !== null) {
            $query->where('package_id', $packageId);
        }

        return $query->exists();
    }

    /**
     * Get active live stream package ids for this user.
     *
     * @return int[]
     */
    public function getActiveLiveStreamPackageIds(): array
    {
        return $this->liveStreamSubscriptions()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->whereNotNull('package_id')
            ->pluck('package_id')
            ->unique()
            ->values()
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Get all login logs for the user.
     */
    public function loginLogs(): HasMany
    {
        return $this->hasMany(LoginLog::class, 'user_id');
    }

    /**
     * Get all device tokens for the user.
     */
    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class, 'user_id');
    }

    /**
     * Get notifications created by the user.
     */
    public function createdNotifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'created_by');
    }

    /**
     * Get payment requests reviewed by the user.
     */
    public function reviewedPaymentRequests(): HasMany
    {
        return $this->hasMany(PaymentRequest::class, 'reviewed_by');
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 2;
    }

    /**
     * Check if user is accountant.
     */
    public function isAccountant(): bool
    {
        return $this->role === 1;
    }

    /**
     * Check if user is regular user.
     */
    public function isUser(): bool
    {
        return $this->role === 0;
    }
}

