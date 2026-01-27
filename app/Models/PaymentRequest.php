<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'payment_type',
        'purpose',
        'created_by',
        'amount',
        'currency',
        'period_months',
        'plan_name',
        'meta',
        'payment_method',
        'payment_method_id',
        'transaction_number',
        'receipt_file',
        'payment_date',
        'status',
        'is_paid',
        'is_deferred',
        'paid_at',
        'reviewed_by',
        'reviewed_at',
        'reject_reason',
        'notes',
        'approved_amount',
        'auto_approved',
        'paid_amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'payment_date' => 'date',
            'reviewed_at' => 'datetime',
            'paid_at' => 'datetime',
            'auto_approved' => 'boolean',
            'is_paid' => 'boolean',
            'is_deferred' => 'boolean',
            'meta' => 'array',
        ];
    }

    /**
     * Get the user that owns the payment request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    /**
     * Get the payment method.
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    /**
     * Get the reviewer.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'reviewed_by');
    }

    /**
     * Get the creator (for cash payments).
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'created_by');
    }

    /**
     * Check if payment is cash.
     */
    public function isCash(): bool
    {
        return $this->payment_type === 'cash';
    }

    /**
     * Check if payment is online.
     */
    public function isOnline(): bool
    {
        return $this->payment_type === 'online';
    }

    /**
     * Check if request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 0;
    }

    /**
     * Check if request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 1;
    }

    /**
     * Check if request is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 2;
    }

    /**
     * Check if payment is paid.
     */
    public function isPaid(): bool
    {
        return $this->is_paid === true;
    }

    /**
     * Check if payment is deferred.
     */
    public function isDeferred(): bool
    {
        return $this->is_deferred === true;
    }

    /**
     * Check if payment is unpaid (deferred or not paid yet).
     */
    public function isUnpaid(): bool
    {
        return !$this->is_paid;
    }

    /**
     * Get the revenue record for this payment.
     */
    public function revenue(): HasOne
    {
        return $this->hasOne(Revenue::class, 'payment_request_id');
    }

    /**
     * Get partial payments for this payment request.
     */
    public function partialPayments(): HasMany
    {
        return $this->hasMany(PartialPayment::class, 'payment_request_id');
    }

    /**
     * Get remaining amount to be paid.
     */
    public function getRemainingAmountAttribute(): float
    {
        $totalAmount = $this->approved_amount ?? $this->amount;
        return max(0, $totalAmount - ($this->paid_amount ?? 0));
    }

    /**
     * Check if payment is fully paid.
     */
    public function isFullyPaid(): bool
    {
        $totalAmount = $this->approved_amount ?? $this->amount;
        $paidAmount = $this->paid_amount ?? 0;
        return $paidAmount >= $totalAmount;
    }
}

