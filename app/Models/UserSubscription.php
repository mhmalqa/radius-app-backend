<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'radius_username',
        'expiration_at',
        'balance',
        'data_limit',
        'data_used',
        'plan_name',
        'auto_renew',
        'firstname',
        'mobile',
        'is_active_radius',
        'is_online',
        'download_kbps',
        'upload_kbps',
        'download_mbps',
        'upload_mbps',
        'download_MB',
        'upload_MB',
        'total_MB',
        'raw_data',
        'last_synced_at',
        'sync_status',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'expiration_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'auto_renew' => 'boolean',
            'is_active_radius' => 'boolean',
            'is_online' => 'boolean',
            'download_mbps' => 'decimal:2',
            'upload_mbps' => 'decimal:2',
            'download_MB' => 'decimal:2',
            'upload_MB' => 'decimal:2',
            'total_MB' => 'decimal:2',
            'raw_data' => 'array',
        ];
    }

    /**
     * Get the user that owns the subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    /**
     * Check if subscription is expired.
     */
    public function isExpired(): bool
    {
        return $this->expiration_at && $this->expiration_at->isPast();
    }

    /**
     * Check if subscription is active.
     */
    public function isActive(): bool
    {
        return !$this->isExpired();
    }

    /**
     * Get data usage percentage.
     */
    public function getDataUsagePercentage(): float
    {
        if (!$this->data_limit || $this->data_limit == 0) {
            return 0;
        }

        return ($this->data_used / $this->data_limit) * 100;
    }

    /**
     * Get remaining data in bytes.
     */
    public function getRemainingData(): ?int
    {
        if (!$this->data_limit) {
            return null;
        }

        return max(0, $this->data_limit - $this->data_used);
    }

    /**
     * Renew subscription by adding months to expiration date.
     */
    public function renew(int $months): void
    {
        $currentExpiration = $this->expiration_at ?? now();
        
        // If subscription is expired, start from now
        if ($this->isExpired()) {
            $newExpiration = now()->addMonths($months);
        } else {
            // If subscription is active, add months to current expiration
            $newExpiration = $currentExpiration->copy()->addMonths($months);
        }

        $this->update([
            'expiration_at' => $newExpiration,
        ]);
    }
}

