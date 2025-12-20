<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'address',
        'subscription_data',
        'description',
        'status',
        'assigned_to',
        'notes',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'subscription_data' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the maintenance request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    /**
     * Get the assigned admin/accountant.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'assigned_to');
    }

    /**
     * Check if request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if request is submitted.
     */
    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    /**
     * Check if request is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if request is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if request is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Get status label in Arabic.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'قيد الانتظار',
            'submitted' => 'تم التقديم',
            'in_progress' => 'قيد التنفيذ',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
            default => 'غير معروف',
        };
    }
}
