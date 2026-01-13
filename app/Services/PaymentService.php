<?php

namespace App\Services;

use App\Models\PaymentRequest;
use App\Models\Revenue;
use App\Models\AppUser;
use App\Models\UserSubscription;
use App\Models\PartialPayment;
use App\Enums\PaymentRequestStatus;
use App\Services\RadiusSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PaymentService
{
    public function __construct(
        protected RadiusSyncService $radiusSyncService
    ) {}
    /**
     * Create a new payment request.
     */
    public function createPaymentRequest(AppUser $user, array $data): PaymentRequest
    {
        $receiptPath = null;

        if (isset($data['receipt_file']) && $data['receipt_file']->isValid()) {
            $receiptPath = $data['receipt_file']->store('receipts', 'public');
        }

        return PaymentRequest::create([
            'user_id' => $user->id,
            'payment_type' => 'online', // Default for user-created payment requests
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'USD',
            'period_months' => $data['period_months'] ?? null,
            'plan_name' => $data['plan_name'] ?? null,
            'payment_method' => $data['payment_method'] ?? null,
            'payment_method_id' => $data['payment_method_id'] ?? null,
            'transaction_number' => $data['transaction_number'] ?? null,
            'receipt_file' => $receiptPath,
            'payment_date' => $data['payment_date'] ?? null,
            'status' => PaymentRequestStatus::PENDING->value,
        ]);
    }

    /**
     * Update payment request status.
     */
    public function updatePaymentRequestStatus(
        PaymentRequest $paymentRequest,
        int $status,
        AppUser $reviewer,
        ?string $rejectReason = null,
        ?string $notes = null,
        ?float $approvedAmount = null,
        ?int $periodMonths = null,
        ?string $planName = null
    ): PaymentRequest {
        DB::transaction(function () use ($paymentRequest, $status, $reviewer, $rejectReason, $notes, $approvedAmount, $periodMonths, $planName) {
            $wasApproved = $paymentRequest->status === PaymentRequestStatus::APPROVED->value;
            $isNowApproved = $status === PaymentRequestStatus::APPROVED->value;

            $updateData = [
                'status' => $status,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'reject_reason' => $rejectReason,
                'notes' => $notes,
                'approved_amount' => $approvedAmount ?? $paymentRequest->amount,
            ];

            // Update period_months if provided
            if ($periodMonths !== null) {
                $updateData['period_months'] = $periodMonths;
            }

            // Update plan_name if provided
            if ($planName !== null) {
                $updateData['plan_name'] = $planName;
            }

            // For online payments, when approved, mark as paid (since online payments are already paid)
            if ($isNowApproved && $paymentRequest->isOnline() && !$paymentRequest->isPaid()) {
                $updateData['is_paid'] = true;
                $updateData['paid_at'] = $paymentRequest->payment_date ? 
                    Carbon::parse($paymentRequest->payment_date) : now();
            }

            $paymentRequest->update($updateData);

            // If approved (and wasn't approved before), renew subscription and add revenue
            if ($isNowApproved && !$wasApproved) {
                $this->processApprovedPayment($paymentRequest);
            }

            // If approved, trigger notification
            if ($isNowApproved) {
                // TODO: Trigger notification service
                // $this->notificationService->sendPaymentApprovedNotification($paymentRequest);
            } elseif ($status === PaymentRequestStatus::REJECTED->value) {
                // TODO: Trigger notification service
                // $this->notificationService->sendPaymentRejectedNotification($paymentRequest);
            }
        });

        return $paymentRequest->fresh();
    }

    /**
     * Process approved payment: renew subscription in Radius and add revenue (only if paid).
     */
    protected function processApprovedPayment(PaymentRequest $paymentRequest): void
    {
        try {
            $user = $paymentRequest->user;
            $periodMonths = $paymentRequest->period_months;
            $planName = $paymentRequest->plan_name ?? null;

            // Renew subscription in Radius if period_months is provided
            // This happens even for deferred payments (renewal happens, but revenue is added later)
            if ($periodMonths && $periodMonths > 0) {
                // Determine paid_status based on payment status
                $paidStatus = $paymentRequest->is_paid ? 'paid' : 'unpaid';
                
                $renewed = $this->radiusSyncService->renewSubscription(
                    $user->username,
                    $periodMonths,
                    $planName,
                    $paidStatus
                );

                if ($renewed) {
                    // Sync subscription data from Radius after renewal
                    $this->radiusSyncService->syncUserSubscription($user);
                } else {
                    Log::warning('Failed to renew subscription in Radius, updating local database only', [
                        'user_id' => $user->id,
                        'username' => $user->username,
                        'months' => $periodMonths,
                    ]);

                    // Fallback: Update local database if Radius update fails
                    $subscription = $user->subscription;
                    
                    if ($subscription) {
                        $subscription->renew($periodMonths);
                    } else {
                        // Create subscription if doesn't exist
                        UserSubscription::create([
                            'user_id' => $user->id,
                            'radius_username' => $user->username,
                            'expiration_at' => now()->addMonths($periodMonths),
                            'plan_name' => $planName,
                        ]);
                    }
                }
            }

            // Add revenue only if payment is paid (not deferred)
            // Check if revenue already exists to avoid duplicates
            if ($paymentRequest->is_paid) {
                $existingRevenue = Revenue::where('payment_request_id', $paymentRequest->id)->first();
                if (!$existingRevenue) {
                    Revenue::create([
                        'payment_request_id' => $paymentRequest->id,
                        'user_id' => $user->id,
                        'amount' => $paymentRequest->approved_amount ?? $paymentRequest->amount,
                        'currency' => $paymentRequest->currency,
                        'period_months' => $periodMonths,
                        'payment_type' => $paymentRequest->payment_type,
                        'payment_date' => $paymentRequest->paid_at?->toDateString() ?? $paymentRequest->payment_date ?? now()->toDateString(),
                        'notes' => $paymentRequest->notes,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to process approved payment', [
                'payment_request_id' => $paymentRequest->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Don't throw exception to avoid rolling back the payment approval
        }
    }

    /**
     * Get user payment requests.
     */
    public function getUserPaymentRequests(AppUser $user, ?int $status = null, ?string $currency = null): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = PaymentRequest::where('user_id', $user->id)
            ->with(['paymentMethod', 'reviewer', 'creator', 'partialPayments.creator'])
            ->orderBy('created_at', 'desc');

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($currency !== null) {
            $query->where('currency', $currency);
        }

        return $query->paginate(15);
    }

    /**
     * Get all payment requests (for admin/accountant).
     */
    public function getAllPaymentRequests(
        ?int $status = null, 
        ?bool $isPaid = null, 
        ?bool $isDeferred = null, 
        ?string $currency = null,
        ?string $search = null,
        ?int $periodMonths = null
    ): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = PaymentRequest::with(['user', 'paymentMethod', 'reviewer', 'creator'])
            ->orderBy('created_at', 'desc');

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($isPaid !== null) {
            $query->where('is_paid', $isPaid);
        }

        if ($isDeferred !== null) {
            $query->where('is_deferred', $isDeferred);
        }

        if ($currency !== null) {
            $query->where('currency', $currency);
        }

        // Filter by period_months
        if ($periodMonths !== null) {
            $query->where('period_months', $periodMonths);
        }

        // Search by username, firstname, phone, or email
        if ($search !== null && $search !== '') {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('firstname', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->paginate(15);
    }

    /**
     * Create a cash payment (for admin/accountant).
     */
    public function createCashPayment(AppUser $user, AppUser $creator, array $data): PaymentRequest
    {
        return DB::transaction(function () use ($user, $creator, $data) {
            $isDeferred = $data['is_deferred'] ?? false;
            $isPaid = !$isDeferred; // If deferred, not paid yet

            $paymentRequest = PaymentRequest::create([
                'user_id' => $user->id,
                'payment_type' => 'cash',
                'created_by' => $creator->id,
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'USD',
                'period_months' => $data['period_months'] ?? null,
                'plan_name' => $data['plan_name'] ?? null,
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'status' => PaymentRequestStatus::APPROVED->value, // Auto-approve cash payments
                'is_paid' => $isPaid,
                'is_deferred' => $isDeferred,
                'paid_at' => $isPaid ? now() : null,
                'reviewed_by' => $creator->id,
                'reviewed_at' => now(),
                'approved_amount' => $data['amount'],
                'auto_approved' => true,
                'notes' => $data['notes'] ?? ($isDeferred ? 'دفعة مؤجلة - لم يتم الدفع بعد' : 'دفعة نقدية من المكتب'),
            ]);

            // Process approved payment (renew subscription and add revenue only if paid)
            $this->processApprovedPayment($paymentRequest);

            return $paymentRequest;
        });
    }

    /**
     * Add partial payment to deferred payment.
     */
    public function addPartialPayment(
        PaymentRequest $paymentRequest,
        AppUser $updater,
        float $amount,
        ?string $notes = null,
        ?string $paymentDate = null
    ): PartialPayment {
        if (!$paymentRequest->isDeferred()) {
            throw new \Exception('هذه الدفعة ليست مؤجلة');
        }

        if ($paymentRequest->status !== PaymentRequestStatus::APPROVED->value) {
            throw new \Exception('لا يمكن إضافة دفعة جزئية لدفعة غير مقبولة');
        }

        $totalAmount = $paymentRequest->approved_amount ?? $paymentRequest->amount;
        $currentPaidAmount = $paymentRequest->paid_amount ?? 0;
        $remainingAmount = $totalAmount - $currentPaidAmount;

        if ($amount <= 0) {
            throw new \Exception('المبلغ يجب أن يكون أكبر من صفر');
        }

        if ($amount > $remainingAmount) {
            throw new \Exception("المبلغ المدخل ({$amount}) أكبر من المبلغ المتبقي ({$remainingAmount})");
        }

        return DB::transaction(function () use ($paymentRequest, $updater, $amount, $notes, $paymentDate, $totalAmount) {
            // Create partial payment record
            $partialPayment = PartialPayment::create([
                'payment_request_id' => $paymentRequest->id,
                'user_id' => $paymentRequest->user_id,
                'created_by' => $updater->id,
                'amount' => $amount,
                'currency' => $paymentRequest->currency,
                'payment_date' => $paymentDate ? Carbon::parse($paymentDate) : now(),
                'notes' => $notes ?? "دفعة جزئية - {$amount} " . $paymentRequest->currency,
            ]);

            // Update paid amount
            $newPaidAmount = ($paymentRequest->paid_amount ?? 0) + $amount;
            $isFullyPaid = $newPaidAmount >= $totalAmount;

            $updateData = [
                'paid_amount' => $newPaidAmount,
            ];

            // If fully paid, mark as paid
            if ($isFullyPaid) {
                $updateData['is_paid'] = true;
                $updateData['paid_at'] = now();
                $updateData['notes'] = ($paymentRequest->notes ?? '') . ' - تم إكمال الدفع في: ' . now()->format('Y-m-d H:i:s');
            } else {
                $updateData['notes'] = ($paymentRequest->notes ?? '') . " - تم دفع {$amount} " . $paymentRequest->currency . " في: " . now()->format('Y-m-d H:i:s');
            }

            $paymentRequest->update($updateData);

            // Add revenue for the partial payment
            Revenue::create([
                'payment_request_id' => $paymentRequest->id,
                'user_id' => $paymentRequest->user_id,
                'amount' => $amount,
                'currency' => $paymentRequest->currency,
                'period_months' => null, // Partial payments don't renew subscription
                'payment_type' => $paymentRequest->payment_type,
                'payment_date' => $partialPayment->payment_date->toDateString(),
                'notes' => $partialPayment->notes . ' (دفعة جزئية)',
            ]);

            return $partialPayment->fresh(['creator']);
        });
    }

    /**
     * Mark deferred payment as paid (full payment).
     */
    public function markPaymentAsPaid(PaymentRequest $paymentRequest, AppUser $updater, ?float $amount = null): PaymentRequest
    {
        if (!$paymentRequest->isDeferred()) {
            throw new \Exception('هذه الدفعة ليست مؤجلة');
        }

        if ($paymentRequest->status !== PaymentRequestStatus::APPROVED->value) {
            throw new \Exception('لا يمكن تحديث حالة الدفع لدفعة غير مقبولة');
        }

        $totalAmount = $paymentRequest->approved_amount ?? $paymentRequest->amount;
        $currentPaidAmount = $paymentRequest->paid_amount ?? 0;
        $remainingAmount = $totalAmount - $currentPaidAmount;

        // If amount is provided, treat it as partial payment
        if ($amount !== null && $amount > 0) {
            $this->addPartialPayment($paymentRequest, $updater, $amount);
            return $paymentRequest->fresh(['revenue', 'partialPayments']);
        }

        // If already fully paid
        if ($paymentRequest->isFullyPaid()) {
            return $paymentRequest->fresh(['revenue', 'partialPayments']);
        }

        return DB::transaction(function () use ($paymentRequest, $updater, $remainingAmount) {
            // Create partial payment record for the remaining amount
            if ($remainingAmount > 0) {
                PartialPayment::create([
                    'payment_request_id' => $paymentRequest->id,
                    'user_id' => $paymentRequest->user_id,
                    'created_by' => $updater->id,
                    'amount' => $remainingAmount,
                    'currency' => $paymentRequest->currency,
                    'payment_date' => now(),
                    'notes' => "إكمال الدفعة المتبقية - {$remainingAmount} " . $paymentRequest->currency,
                ]);
            }

            $paymentRequest->update([
                'is_paid' => true,
                'paid_amount' => $paymentRequest->approved_amount ?? $paymentRequest->amount,
                'paid_at' => now(),
                'notes' => ($paymentRequest->notes ?? '') . ' - تم إكمال الدفع في: ' . now()->format('Y-m-d H:i:s'),
            ]);

            // Add revenue for remaining amount if not already added
            $existingRevenue = Revenue::where('payment_request_id', $paymentRequest->id)
                ->where('amount', $remainingAmount)
                ->first();
            
            if (!$existingRevenue && $remainingAmount > 0) {
                Revenue::create([
                    'payment_request_id' => $paymentRequest->id,
                    'user_id' => $paymentRequest->user_id,
                    'amount' => $remainingAmount,
                    'currency' => $paymentRequest->currency,
                    'period_months' => null,
                    'payment_type' => $paymentRequest->payment_type,
                    'payment_date' => now()->toDateString(),
                    'notes' => $paymentRequest->notes . ' (إكمال الدفعة)',
                ]);
            }

            return $paymentRequest->fresh(['revenue', 'partialPayments']);
        });
    }
}

