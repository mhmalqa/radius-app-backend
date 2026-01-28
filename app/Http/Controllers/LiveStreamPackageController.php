<?php

namespace App\Http\Controllers;

use App\Http\Requests\LiveStreamPackage\CreateLiveStreamPackageRequest;
use App\Http\Requests\LiveStreamPackage\PurchaseLiveStreamPackageRequest;
use App\Http\Resources\LiveStreamPackageResource;
use App\Http\Resources\LiveStreamSubscriptionResource;
use App\Http\Resources\PaymentRequestResource;
use App\Models\LiveStreamPackage;
use App\Models\LiveStreamSubscription;
use App\Models\AppUser;
use App\Services\PaymentService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LiveStreamPackageController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected NotificationService $notificationService
    ) {}

    /**
     * List active packages (for app users).
     */
    public function index(Request $request): JsonResponse
    {
        $packages = LiveStreamPackage::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => LiveStreamPackageResource::collection($packages),
        ]);
    }

    /**
     * Get user's active live stream subscription (if any).
     */
    public function mySubscription(Request $request): JsonResponse
    {
        $user = $request->user();

        $sub = LiveStreamSubscription::query()
            ->with('package')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->orderByDesc('expires_at')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $sub ? new LiveStreamSubscriptionResource($sub) : null,
        ]);
    }

    /**
     * Get user's active live stream subscriptions (all packages).
     */
    public function mySubscriptions(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get all subscriptions that are active OR expired within last 4 days
        $allSubs = LiveStreamSubscription::query()
            ->with('package')
            ->where('user_id', $user->id)
            ->where(function ($q) {
                $q->where('expires_at', '>', now()) // Active
                  ->orWhere('expires_at', '>', now()->subDays(4)); // Recently expired (within 4 days)
            })
            ->orderByDesc('expires_at')
            ->get();

        // Filter: Show all active, but only the LATEST single expired subscription
        $activeSubs = $allSubs->filter(fn ($sub) => $sub->expires_at > now());
        $expiredSubs = $allSubs->filter(fn ($sub) => $sub->expires_at <= now());

        // If there are expired subscriptions, take only the most recent one
        $latestExpired = $expiredSubs->first(); // Already ordered by desc

        $result = $activeSubs;
        if ($latestExpired) {
            $result->push($latestExpired);
        }

        return response()->json([
            'success' => true,
            'data' => LiveStreamSubscriptionResource::collection($result),
        ]);
    }

    /**
     * Purchase a package (creates a PaymentRequest or activates immediately if free).
     */
    public function purchase(PurchaseLiveStreamPackageRequest $request, LiveStreamPackage $package): JsonResponse
    {
        $user = $request->user();

        if (!$package->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'هذه الباقة غير متاحة حالياً',
            ], 404);
        }

        // Optional safety: require base subscription to be active as well
        $hasActiveBaseSubscription = $user->subscription && $user->subscription->isActive();
        if (!$hasActiveBaseSubscription) {
            return response()->json([
                'success' => false,
                'message' => 'يجب أن يكون لديك اشتراك إنترنت نشط أولاً',
            ], 403);
        }

        // Free package: activate instantly
        if ((float) $package->price <= 0) {
            $now = now();
            $lastActive = LiveStreamSubscription::where('user_id', $user->id)
                ->where('package_id', $package->id) // Only extend if same package
                ->where('status', 'active')
                ->where('expires_at', '>', $now)
                ->orderByDesc('expires_at')
                ->first();

            $base = $lastActive ? $lastActive->expires_at->copy() : $now;

            $sub = LiveStreamSubscription::create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'payment_request_id' => null,
                'starts_at' => $now,
                'expires_at' => $base->addDays((int) $package->duration_days),
                'status' => 'active',
                'renewed_from_id' => $lastActive?->id,
            ])->load('package');

            return response()->json([
                'success' => true,
                'message' => 'تم تفعيل باقة البث بنجاح',
                'data' => [
                    'subscription' => new LiveStreamSubscriptionResource($sub),
                    'payment_request' => null,
                ],
            ], 201);
        }

        // Paid package: create payment request with purpose/meta
        $validated = $request->validated();
        $validated['amount'] = (float) $package->price;
        $validated['currency'] = $package->currency ?? 'USD';
        $validated['purpose'] = 'live_stream_subscription';
        $validated['plan_name'] = $package->name;
        $validated['period_months'] = null; // IMPORTANT: do not renew Radius
        $validated['meta'] = [
            'package_id' => $package->id,
        ];

        $validated['receipt_file'] = $request->file('receipt_file');

        $paymentRequest = $this->paymentService->createPaymentRequest($user, $validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال طلب الاشتراك بالبث بنجاح وسيتم تفعيله بعد الموافقة',
            'data' => [
                'payment_request' => new PaymentRequestResource($paymentRequest->load(['paymentMethod'])),
            ],
        ], 201);
    }

    /**
     * Admin: list packages.
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $this->authorize('viewAny', LiveStreamPackage::class);

        $query = LiveStreamPackage::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $packages = $query->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => LiveStreamPackageResource::collection($packages->items()),
            'meta' => [
                'current_page' => $packages->currentPage(),
                'last_page' => $packages->lastPage(),
                'per_page' => $packages->perPage(),
                'total' => $packages->total(),
            ],
        ]);
    }

    /**
     * Admin: create package.
     */
    public function store(CreateLiveStreamPackageRequest $request): JsonResponse
    {
        $this->authorize('create', LiveStreamPackage::class);

        $data = $request->validated();
        $package = LiveStreamPackage::create($data);

        // Send notification to all active users
        try {
            $userIds = AppUser::where('is_active', true)->pluck('id')->toArray();

            if (!empty($userIds)) {
                $this->notificationService->createNotification([
                    'title' => "📦 باقة بث جديدة: {$package->name}",
                    'body' => "تم إضافة باقة جديدة للبث المباشر. تصفح الباقات الآن!",
                    'type' => 'system',
                    'priority' => 1,
                    'action_url' => "/live-stream-packages",
                    'action_text' => 'عرض الباقات',
                ], $userIds, 'specific');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send new package notification: " . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء باقة البث بنجاح',
            'data' => new LiveStreamPackageResource($package),
        ], 201);
    }

    /**
     * Admin: update package.
     */
    public function update(CreateLiveStreamPackageRequest $request, LiveStreamPackage $package): JsonResponse
    {
        $this->authorize('update', $package);

        $data = $request->validated();
        $package->update($data);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث باقة البث بنجاح',
            'data' => new LiveStreamPackageResource($package->fresh()),
        ]);
    }

    /**
     * Admin: delete package.
     */
    public function destroy(LiveStreamPackage $package): JsonResponse
    {
        $this->authorize('delete', $package);

        $package->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف باقة البث بنجاح',
        ]);
    }
}

