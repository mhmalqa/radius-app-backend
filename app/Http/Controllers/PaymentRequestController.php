<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest\CreatePaymentRequest;
use App\Http\Requests\PaymentRequest\CreateCashPaymentRequest;
use App\Http\Requests\PaymentRequest\UpdatePaymentRequestStatus;
use App\Http\Resources\PaymentRequestResource;
use App\Models\AppUser;
use App\Models\LiveStreamPackage;
use App\Models\PaymentRequest;
use App\Enums\PaymentRequestStatus;
use App\Services\PaymentService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentRequestController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected NotificationService $notificationService
    ) {}

    /**
     * Get user's payment requests.
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $currency = $request->query('currency');

        // Validate currency if provided
        if ($currency !== null && !in_array($currency, ['USD', 'SYP', 'TRY'])) {
            return response()->json([
                'success' => false,
                'message' => 'العملة يجب أن تكون USD أو SYP أو TRY',
            ], 422);
        }

        $paymentRequests = $this->paymentService->getUserPaymentRequests(
            $request->user(),
            $status !== null ? (int) $status : null,
            $currency
        );

        return response()->json([
            'success' => true,
            'data' => PaymentRequestResource::collection($paymentRequests->items()),
            'meta' => [
                'current_page' => $paymentRequests->currentPage(),
                'last_page' => $paymentRequests->lastPage(),
                'per_page' => $paymentRequests->perPage(),
                'total' => $paymentRequests->total(),
            ],
        ]);
    }

    /**
     * Create new payment request.
     */
    public function store(CreatePaymentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['receipt_file'] = $request->file('receipt_file');

        $paymentRequest = $this->paymentService->createPaymentRequest($request->user(), $data);

        // إرسال إشعار للمحاسبين والمديرين
        $this->notificationService->sendToAdminsAndAccountants([
            'title' => 'طلب دفع جديد',
            'body' => "تم إرسال طلب دفع جديد من المستخدم {$request->user()->username} بمبلغ {$paymentRequest->amount} {$paymentRequest->currency}",
            'type' => 'system',
            'priority' => 1,
            'action_url' => "/admin/payment-requests/{$paymentRequest->id}",
            'action_text' => 'عرض الطلب',
            'icon' => 'payment',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال طلب الدفع بنجاح',
            'data' => new PaymentRequestResource($paymentRequest->load(['paymentMethod'])),
        ], 201);
    }

    /**
     * Get single payment request.
     */
    public function show(Request $request, PaymentRequest $paymentRequest): JsonResponse
    {
        // Check if user owns this payment request or is admin/accountant
        if ($paymentRequest->user_id !== $request->user()->id && !$request->user()->isAdmin() && !$request->user()->isAccountant()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالوصول إلى هذا الطلب',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new PaymentRequestResource($paymentRequest->load(['user', 'paymentMethod', 'reviewer', 'partialPayments.creator'])),
        ]);
    }

    /**
     * Get all payment requests (admin/accountant only).
     */
    public function all(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PaymentRequest::class);

        $status = $request->query('status');
        $isPaid = $request->has('is_paid') ? $request->boolean('is_paid') : null;
        $isDeferred = $request->has('is_deferred') ? $request->boolean('is_deferred') : null;
        $currency = $request->query('currency');
        $search = $request->query('search');
        $periodMonths = $request->query('period_months');

        // Validate currency if provided
        if ($currency !== null && !in_array($currency, ['USD', 'SYP', 'TRY'])) {
            return response()->json([
                'success' => false,
                'message' => 'العملة يجب أن تكون USD أو SYP أو TRY',
            ], 422);
        }

        // Validate period_months if provided
        if ($periodMonths !== null && (!is_numeric($periodMonths) || $periodMonths < 0)) {
            return response()->json([
                'success' => false,
                'message' => 'عدد الأشهر يجب أن يكون رقماً صحيحاً أكبر من أو يساوي صفر',
            ], 422);
        }

        $paymentRequests = $this->paymentService->getAllPaymentRequests(
            $status !== null ? (int) $status : null,
            $isPaid,
            $isDeferred,
            $currency,
            $search,
            $periodMonths !== null ? (int) $periodMonths : null
        );

        return response()->json([
            'success' => true,
            'data' => PaymentRequestResource::collection($paymentRequests->items()),
            'meta' => [
                'current_page' => $paymentRequests->currentPage(),
                'last_page' => $paymentRequests->lastPage(),
                'per_page' => $paymentRequests->perPage(),
                'total' => $paymentRequests->total(),
            ],
        ]);
    }

    /**
     * Update payment request status (admin/accountant only).
     */
    public function updateStatus(Request $request, PaymentRequest $paymentRequest, UpdatePaymentRequestStatus $updateRequest): JsonResponse
    {
        $this->authorize('update', $paymentRequest);

        $validated = $updateRequest->validated();

        $wasApproved = $paymentRequest->status === 1;
        $paymentRequest = $this->paymentService->updatePaymentRequestStatus(
            $paymentRequest,
            $validated['status'],
            $request->user(),
            $validated['reject_reason'] ?? null,
            $validated['notes'] ?? null,
            $validated['approved_amount'] ?? null,
            $validated['period_months'] ?? null,
            $validated['plan_name'] ?? null
        );

        // إرسال إشعار للمستخدم عند قبول/رفض طلبه
        if ($validated['status'] === 1 && !$wasApproved) {
            // قبول الطلب
            $approvedAmount = $paymentRequest->approved_amount ?? $paymentRequest->amount;
            $notification = $this->notificationService->createNotification([
                'title' => 'تم قبول طلب الدفع',
                'body' => "تم قبول طلب الدفع الخاص بك بمبلغ {$approvedAmount} {$paymentRequest->currency}",
                'type' => 'system',
                'priority' => 1,
                'action_url' => "/payment-requests/{$paymentRequest->id}",
                'action_text' => 'عرض الطلب',
                'icon' => 'check_circle',
            ], [$paymentRequest->user_id], 'specific');
        } elseif ($validated['status'] === 2) {
            // رفض الطلب
            $notification = $this->notificationService->createNotification([
                'title' => 'تم رفض طلب الدفع',
                'body' => "تم رفض طلب الدفع الخاص بك. السبب: " . ($validated['reject_reason'] ?? 'غير محدد'),
                'type' => 'system',
                'priority' => 2,
                'action_url' => "/payment-requests/{$paymentRequest->id}",
                'action_text' => 'عرض الطلب',
                'icon' => 'cancel',
            ], [$paymentRequest->user_id], 'specific');
        }

        return response()->json([
            'success' => true,
            'message' => $validated['status'] === 1 ? 'تم قبول الطلب بنجاح' : 'تم رفض الطلب',
            'data' => new PaymentRequestResource($paymentRequest->load(['user', 'paymentMethod', 'reviewer'])),
        ]);
    }

    /**
     * Create cash payment (admin/accountant only).
     */
    public function createCashPayment(CreateCashPaymentRequest $request): JsonResponse
    {
        $this->authorize('create', PaymentRequest::class);

        $validated = $request->validated();
        $user = AppUser::findOrFail($validated['user_id']);

        $purpose = $validated['purpose'] ?? 'internet_subscription';
        if ($purpose === 'live_stream_subscription') {
            $package = LiveStreamPackage::findOrFail($validated['package_id']);

            // Force correct mapping for live stream subscription cash payment
            $validated['purpose'] = 'live_stream_subscription';
            $validated['period_months'] = null; // IMPORTANT: do not renew Radius
            $validated['plan_name'] = $package->name;
            $validated['currency'] = $validated['currency'] ?? $package->currency ?? 'USD';
            $validated['meta'] = [
                'package_id' => $package->id,
            ];
        }

        $paymentRequest = $this->paymentService->createCashPayment(
            $user,
            $request->user(),
            $validated
        );

        // إرسال إشعار للمستخدم
        $isLive = ($paymentRequest->purpose ?? 'internet_subscription') === 'live_stream_subscription';

        $userNotificationTitle = $paymentRequest->is_deferred
            ? ($isLive ? 'تم تفعيل اشتراك البث (دفع مؤجل)' : 'تم تجديد الاشتراك (دفع مؤجل)')
            : ($isLive ? 'تم تفعيل اشتراك البث بنجاح' : 'تم تجديد الاشتراك بنجاح');

        $userNotificationBody = $paymentRequest->is_deferred
            ? ($isLive
                ? "تم تفعيل اشتراك البث بنجاح وتسجيل دفعة مؤجلة بمبلغ {$paymentRequest->amount} {$paymentRequest->currency}"
                : "تم تجديد اشتراكك بنجاح وتسجيل دفعة مؤجلة بمبلغ {$paymentRequest->amount} {$paymentRequest->currency}")
            : ($isLive
                ? "تم تفعيل اشتراك البث بنجاح واستلام مبلغ {$paymentRequest->amount} {$paymentRequest->currency}"
                : "تم تجديد اشتراكك بنجاح واستلام مبلغ {$paymentRequest->amount} {$paymentRequest->currency}");

        $this->notificationService->createNotification([
            'title' => $userNotificationTitle,
            'body' => $userNotificationBody,
            'type' => 'system',
            'priority' => 1,
            'action_url' => "/payment-requests/{$paymentRequest->id}",
            'action_text' => 'عرض التفاصيل',
            'icon' => 'check_circle',
        ], [$user->id], 'specific');

        // إرسال إشعار للمديرين عند إضافة دفعة نقدية
        $this->notificationService->sendToAdmins([
            'title' => $paymentRequest->is_deferred ? 'دفعة مؤجلة جديدة' : 'دفعة نقدية جديدة',
            'body' => "تم إضافة " . ($paymentRequest->is_deferred ? 'دفعة مؤجلة' : 'دفعة نقدية') . " للمستخدم {$user->username} بمبلغ {$paymentRequest->amount} {$paymentRequest->currency}",
            'type' => 'system',
            'priority' => 1,
            'action_url' => "/admin/payment-requests/{$paymentRequest->id}",
            'action_text' => 'عرض الدفعة',
            'icon' => 'payment',
        ]);

        $message = $paymentRequest->is_deferred
            ? ($isLive
                ? 'تم إضافة دفعة مؤجلة لاشتراك البث. تم التفعيل ولكن الدفعة غير مدفوعة بعد'
                : 'تم إضافة الدفعة المؤجلة بنجاح. سيتم تجديد الاشتراك ولكن الدفعة غير مدفوعة بعد')
            : ($isLive ? 'تم إضافة دفعة نقدية لاشتراك البث بنجاح' : 'تم إضافة الدفعة النقدية بنجاح');

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => new PaymentRequestResource($paymentRequest->load(['user', 'paymentMethod', 'reviewer', 'creator'])),
        ], 201);
    }

    /**
     * Mark deferred payment as paid (admin/accountant only).
     * Can accept partial payment amount.
     */
    public function markAsPaid(Request $request, PaymentRequest $paymentRequest): JsonResponse
    {
        $this->authorize('update', $paymentRequest);

        try {
            $amount = $request->input('amount'); // Optional: partial payment amount
            $notes = $request->input('notes');
            $paymentDate = $request->input('payment_date');

            // If amount is provided, add as partial payment
            if ($amount !== null && $amount > 0) {
                $partialPayment = $this->paymentService->addPartialPayment(
                    $paymentRequest,
                    $request->user(),
                    (float) $amount,
                    $notes,
                    $paymentDate
                );

                $paymentRequest = $paymentRequest->fresh(['user', 'paymentMethod', 'reviewer', 'creator', 'revenue', 'partialPayments.creator']);

                $totalAmount = $paymentRequest->approved_amount ?? $paymentRequest->amount;
                $paidAmount = $paymentRequest->paid_amount ?? 0;
                $remainingAmount = $totalAmount - $paidAmount;

                // إرسال إشعار للمستخدم
                $this->notificationService->createNotification([
                    'title' => $paymentRequest->isFullyPaid() ? 'تم سداد الدفعة المؤجلة بالكامل' : 'تم استلام دفعة جزئية',
                    'body' => $paymentRequest->isFullyPaid()
                        ? "تم سداد الدفعة المؤجلة الخاصة بك بالكامل."
                        : "تم استلام دفعة جزئية بقيمة {$amount} {$paymentRequest->currency}. المتبقي: {$remainingAmount} {$paymentRequest->currency}",
                    'type' => 'system',
                    'priority' => 1,
                    'action_url' => "/payment-requests/{$paymentRequest->id}",
                    'action_text' => 'عرض التفاصيل',
                    'icon' => 'payment',
                ], [$paymentRequest->user_id], 'specific');

                // إرسال إشعار للمديرين عند إضافة دفعة جزئية
                $this->notificationService->sendToAdmins([
                    'title' => $paymentRequest->isFullyPaid() ? 'تم إكمال دفعة مؤجلة' : 'دفعة جزئية جديدة',
                    'body' => $paymentRequest->isFullyPaid()
                        ? "تم إكمال الدفعة المؤجلة للمستخدم {$paymentRequest->user->username} بالكامل"
                        : "تم إضافة دفعة جزئية بقيمة {$amount} {$paymentRequest->currency} للمستخدم {$paymentRequest->user->username}. المتبقي: {$remainingAmount}",
                    'type' => 'system',
                    'priority' => 1,
                    'action_url' => "/admin/payment-requests/{$paymentRequest->id}",
                    'action_text' => 'عرض الدفعة',
                    'icon' => 'payment',
                ]);

                $message = $paymentRequest->isFullyPaid()
                    ? "تم إكمال الدفعة بنجاح. المبلغ الكلي: {$totalAmount}، المدفوع: {$paidAmount}"
                    : "تم إضافة دفعة جزئية بنجاح. المبلغ المدفوع: {$paidAmount}، المتبقي: {$remainingAmount}";

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => new PaymentRequestResource($paymentRequest),
                    'partial_payment' => [
                        'id' => $partialPayment->id,
                        'amount' => (float) $partialPayment->amount,
                        'payment_date' => $partialPayment->payment_date->format('Y-m-d'),
                    ],
                ]);
            }

            // Full payment (mark as completely paid)
            $paymentRequest = $this->paymentService->markPaymentAsPaid(
                $paymentRequest,
                $request->user()
            );

            // إرسال إشعار للمستخدم
            $this->notificationService->createNotification([
                'title' => 'تم سداد الدفعة المؤجلة',
                'body' => "تم سداد الدفعة المؤجلة الخاصة بك بالكامل.",
                'type' => 'system',
                'priority' => 1,
                'action_url' => "/payment-requests/{$paymentRequest->id}",
                'action_text' => 'عرض التفاصيل',
                'icon' => 'check_circle',
            ], [$paymentRequest->user_id], 'specific');

            // إرسال إشعار للمديرين عند إكمال الدفعة
            $this->notificationService->sendToAdmins([
                'title' => 'تم إكمال دفعة مؤجلة',
                'body' => "تم إكمال الدفعة المؤجلة للمستخدم {$paymentRequest->user->username} بالكامل",
                'type' => 'system',
                'priority' => 1,
                'action_url' => "/admin/payment-requests/{$paymentRequest->id}",
                'action_text' => 'عرض الدفعة',
                'icon' => 'check_circle',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث حالة الدفعة إلى مدفوع بنجاح',
                'data' => new PaymentRequestResource($paymentRequest->load(['user', 'paymentMethod', 'reviewer', 'creator', 'revenue', 'partialPayments.creator'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get unpaid deferred installments with amounts by currency for the authenticated user.
     */
    public function unpaidDeferredInstallments(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get unpaid deferred installments with details
        // Unpaid means: is_paid is false or null
        $unpaidDeferred = PaymentRequest::where('user_id', $user->id)
            ->where('is_deferred', true)
            ->where(function ($query) {
                $query->where('is_paid', false)
                      ->orWhereNull('is_paid');
            })
            ->where('status', PaymentRequestStatus::APPROVED->value)
            ->orderBy('created_at', 'desc')
            ->get();

        // Group by currency and calculate totals
        $byCurrency = [
            'USD' => [
                'installments' => [],
                'total_amount' => 0,
                'count' => 0,
            ],
            'SYP' => [
                'installments' => [],
                'total_amount' => 0,
                'count' => 0,
            ],
            'TRY' => [
                'installments' => [],
                'total_amount' => 0,
                'count' => 0,
            ],
        ];

        foreach ($unpaidDeferred as $payment) {
            $currency = $payment->currency ?? 'USD';

            // Calculate remaining amount (total - paid)
            $totalAmount = $payment->approved_amount ?? $payment->amount;
            $paidAmount = $payment->paid_amount ?? 0;
            $remainingAmount = max(0, $totalAmount - $paidAmount);

            // Add to currency group
            if (isset($byCurrency[$currency])) {
                $byCurrency[$currency]['installments'][] = [
                    'id' => $payment->id,
                    'amount' => (float) $totalAmount,
                    'paid_amount' => (float) $paidAmount,
                    'remaining_amount' => (float) $remainingAmount,
                    'currency' => $currency,
                    'period_months' => $payment->period_months,
                    'plan_name' => $payment->plan_name,
                    'payment_date' => $payment->payment_date?->format('Y-m-d'),
                    'created_at' => $payment->created_at->toIso8601String(),
                    'is_fully_paid' => $payment->isFullyPaid(),
                ];
                $byCurrency[$currency]['total_amount'] += $remainingAmount;
                $byCurrency[$currency]['count']++;
            }
        }

        // Format totals
        $result = [];
        foreach ($byCurrency as $currency => $data) {
            $result[$currency] = [
                'installments' => $data['installments'],
                'total_amount' => round($data['total_amount'], 2),
                'count' => $data['count'],
            ];
        }

        // Calculate grand totals
        $grandTotal = [
            'USD' => $result['USD']['total_amount'],
            'SYP' => $result['SYP']['total_amount'],
            'TRY' => $result['TRY']['total_amount'],
        ];
        $totalCount = $result['USD']['count'] + $result['SYP']['count'] + $result['TRY']['count'];

        return response()->json([
            'success' => true,
            'message' => 'تم جلب الدفعات المؤجلة غير المدفوعة بنجاح',
            'data' => [
                'by_currency' => $result,
                'totals' => [
                    'by_currency' => $grandTotal,
                    'total_count' => $totalCount,
                ],
            ],
        ]);
    }
}

