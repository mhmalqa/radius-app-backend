<?php

namespace App\Http\Controllers;

use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DeviceTokenController extends Controller
{
    /**
     * Register device token for push notifications.
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_token' => ['required', 'string', 'max:500'],
            'device_type' => ['required', 'string', 'in:android,ios,web'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $validated = $validator->validated();

        try {
            // Validate token format
            $fcmService = app(\App\Services\FirebaseMessagingService::class);
            if (!$fcmService->validateToken($validated['device_token'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'صيغة التوكن غير صحيحة',
                ], 422);
            }

            // Delete token from any other user (to prevent duplicate tokens across users)
            // This ensures that a device token can only belong to one user at a time
            $deletedTokens = DeviceToken::where('device_token', $validated['device_token'])
                ->where('user_id', '!=', $user->id)
                ->delete();

            if ($deletedTokens > 0) {
                Log::info('Removed device token from other users', [
                    'device_token' => substr($validated['device_token'], 0, 20) . '...',
                    'deleted_count' => $deletedTokens,
                    'new_user_id' => $user->id,
                ]);
            }

            // Also deactivate any existing tokens for this user with the same token (if any)
            DeviceToken::where('user_id', $user->id)
                ->where('device_token', $validated['device_token'])
                ->update(['is_active' => false]);

            // Create or update device token for current user
            $deviceToken = DeviceToken::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'device_token' => $validated['device_token'],
                ],
                [
                    'device_type' => $validated['device_type'],
                    'device_name' => $validated['device_name'] ?? 'Unknown Device',
                    'is_active' => true,
                    'last_used_at' => now(),
                ]
            );

            Log::info('Device token registered', [
                'user_id' => $user->id,
                'device_type' => $validated['device_type'],
                'device_token_id' => $deviceToken->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الجهاز بنجاح',
                'data' => [
                    'id' => $deviceToken->id,
                    'device_type' => $deviceToken->device_type,
                    'device_name' => $deviceToken->device_name,
                    'is_active' => $deviceToken->is_active,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to register device token', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في تسجيل الجهاز',
            ], 500);
        }
    }

    /**
     * Remove device token.
     */
    public function remove(Request $request, DeviceToken $deviceToken): JsonResponse
    {
        $user = $request->user();

        // Check if device token belongs to user
        if ($deviceToken->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك',
            ], 403);
        }

        try {
            $deviceToken->update(['is_active' => false]);

            Log::info('Device token deactivated', [
                'user_id' => $user->id,
                'device_token_id' => $deviceToken->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إلغاء تفعيل الجهاز بنجاح',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to deactivate device token', [
                'user_id' => $user->id,
                'device_token_id' => $deviceToken->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في إلغاء تفعيل الجهاز',
            ], 500);
        }
    }

    /**
     * Get user's device tokens.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $deviceTokens = DeviceToken::where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('last_used_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $deviceTokens->map(function ($token) {
                return [
                    'id' => $token->id,
                    'device_type' => $token->device_type,
                    'device_name' => $token->device_name,
                    'is_active' => $token->is_active,
                    'last_used_at' => $token->last_used_at?->toIso8601String(),
                    'created_at' => $token->created_at->toIso8601String(),
                ];
            }),
        ]);
    }
}

