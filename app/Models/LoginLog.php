<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'username',
        'ip_address',
        'user_agent',
        'device_type',
        'status',
        'failure_reason',
    ];

    /**
     * Get the user (if login was successful).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    /**
     * Check if login was successful.
     */
    public function isSuccess(): bool
    {
        return $this->status === 1;
    }

    /**
     * Check if login failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 0;
    }
}

