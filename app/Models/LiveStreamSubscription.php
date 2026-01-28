<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveStreamSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'package_id',
        'payment_request_id',
        'starts_at',
        'expires_at',
        'status',
        'cancelled_at',
        'renewed_from_id',
        'notification_flags',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'notification_flags' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(LiveStreamPackage::class, 'package_id');
    }

    public function paymentRequest(): BelongsTo
    {
        return $this->belongsTo(PaymentRequest::class, 'payment_request_id');
    }

    public function renewedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'renewed_from_id');
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        return $this->expires_at->isFuture();
    }
}

