<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserSubscriptionResource;
use App\Models\AppUser;
use App\Services\RadiusSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected RadiusSyncService $radiusSyncService
    ) {}

    /**
     * Get user profile.
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user()->load('subscription');

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user),
                'subscription' => $user->subscription ? new UserSubscriptionResource($user->subscription) : null,
            ],
        ]);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        // Get validated data (already excludes protected fields)
        $data = $request->validated();

        // Additional protection: explicitly remove protected fields if somehow sent
        $protectedFields = ['username', 'firstname', 'phone'];
        foreach ($protectedFields as $field) {
            unset($data[$field]);
        }

        // Update only allowed fields
        $user->update($data);

        // Update device token if provided
        if ($request->has('device_token')) {
            $user->deviceTokens()->updateOrCreate(
                ['device_token' => $request->device_token],
                [
                    'device_type' => $request->input('device_type', 'web'),
                    'device_name' => $request->input('device_name'),
                    'is_active' => true,
                    'last_used_at' => now(),
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الملف الشخصي بنجاح',
            'data' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Sync subscription from Radius (saves to database).
     */
    public function syncSubscription(Request $request): JsonResponse
    {
        $success = $this->radiusSyncService->syncUserSubscription($request->user());

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في مزامنة بيانات الاشتراك',
            ], 500);
        }

        $user = $request->user()->load('subscription');

        return response()->json([
            'success' => true,
            'message' => 'تم مزامنة بيانات الاشتراك بنجاح',
            'data' => [
                'subscription' => $user->subscription ? new UserSubscriptionResource($user->subscription) : null,
            ],
        ]);
    }

    /**
     * Get subscription data directly from Radius API (real-time, no database save).
     */
    public function getSubscriptionFromRadius(Request $request): JsonResponse
    {
        $data = $this->radiusSyncService->getUserDataFromRadius($request->user()->username);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب بيانات الاشتراك من Radius',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم جلب بيانات الاشتراك بنجاح',
            'data' => [
                'subscription' => $data,
                'fetched_at' => now()->toIso8601String(),
                'source' => 'radius_api_direct',
            ],
        ]);
    }

    /**
     * Get available services/plans list directly from Radius API.
     */
    public function getServicesFromRadius(): JsonResponse
    {
        $data = $this->radiusSyncService->getServicesFromRadius();

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب الباقات من Radius',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم جلب الباقات من Radius بنجاح',
            'data' => [
                // نعيد البيانات كما هي من الراديوس ليستفيد منها الفرونت بالكامل
                'radius_response' => $data,
                'services' => $data['services'] ?? [],
                'count' => $data['count'] ?? count($data['services'] ?? []),
                'fetched_at' => now()->toIso8601String(),
                'source' => 'radius_services_api_direct',
            ],
        ]);
    }

    /**
     * Get user's current plan by username.
     * Retrieves plan information directly from Radius API.
     */
    public function getUserPlanByUsername(Request $request): JsonResponse
    {
        $username = $request->input('username') ?? $request->route('username');

        if (!$username) {
            return response()->json([
                'success' => false,
                'message' => 'اسم المستخدم مطلوب',
            ], 400);
        }

        $data = $this->radiusSyncService->getUserDataFromRadius($username);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب بيانات الخطة من Radius أو المستخدم غير موجود',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم جلب معلومات الخطة بنجاح',
            'data' => [
                'username' => $username,
                'plan' => [
                    'plan_name' => $data['plan_name'] ?? null,
                    'expiration_at' => $data['expiration_at'] ?? null,
                    'balance' => $data['balance'] ?? null,
                    'data_limit' => $data['data_limit'] ?? null,
                    'data_used' => $data['data_used'] ?? null,
                    'data_usage_percentage' => $data['data_limit'] && $data['data_used']
                        ? round(($data['data_used'] / $data['data_limit']) * 100, 2)
                        : null,
                    'remaining_data' => $data['data_limit'] && $data['data_used']
                        ? max(0, $data['data_limit'] - $data['data_used'])
                        : null,
                    'auto_renew' => $data['auto_renew'] ?? false,
                    'is_active' => $data['is_active_radius'] ?? null,
                    'is_online' => $data['is_online'] ?? null,
                ],
                'speed' => [
                    'download_kbps' => $data['download_kbps'] ?? null,
                    'upload_kbps' => $data['upload_kbps'] ?? null,
                    'download_mbps' => $data['download_mbps'] ?? null,
                    'upload_mbps' => $data['upload_mbps'] ?? null,
                ],
                'usage' => [
                    'download_MB' => $data['download_MB'] ?? null,
                    'upload_MB' => $data['upload_MB'] ?? null,
                    'total_MB' => $data['total_MB'] ?? null,
                ],
                'user_info' => [
                    'firstname' => $data['firstname'] ?? null,
                    'mobile' => $data['mobile'] ?? null,
                ],
                'fetched_at' => now()->toIso8601String(),
                'source' => 'radius_api_direct',
            ],
        ]);
    }
}

