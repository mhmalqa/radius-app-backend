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
}

