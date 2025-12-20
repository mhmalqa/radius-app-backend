<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sync_type',
        'status',
        'error_message',
        'records_synced',
    ];

    /**
     * Get the user (if sync was for specific user).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    /**
     * Check if sync was successful.
     */
    public function isSuccess(): bool
    {
        return $this->status === 0;
    }

    /**
     * Check if sync failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 1;
    }
}

