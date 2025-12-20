<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
        'type',
        'priority',
        'action_url',
        'action_text',
        'icon',
        'sound',
        'badge',
        'created_by',
    ];

    /**
     * Get the user who created the notification.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'created_by');
    }

    /**
     * Get all users who received this notification.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(AppUser::class, 'notification_user', 'notification_id', 'user_id')
            ->withPivot('is_read', 'is_sent', 'sent_at', 'read_at', 'send_error')
            ->withTimestamps();
    }

    /**
     * Check if notification is system notification.
     */
    public function isSystem(): bool
    {
        return $this->type === 'system';
    }

    /**
     * Check if notification is manual notification.
     */
    public function isManual(): bool
    {
        return $this->type === 'manual';
    }
}

