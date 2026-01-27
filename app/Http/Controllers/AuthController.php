<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Login user.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->username,
            $request->password,
            $request->ip(),
            $request->userAgent()
        );

        if (!$result['success']) {
            // تحديد رسالة الخطأ حسب نوع الخطأ
            $message = match ($result['error']) {
                'account_disabled' => 'حسابك معطل. يرجى التواصل مع فريق الدعم لتفعيل حسابك.',
                'invalid_credentials' => 'بيانات الدخول غير صحيحة. يرجى التحقق من اسم المستخدم وكلمة المرور.',
                default => 'فشل تسجيل الدخول. يرجى المحاولة مرة أخرى.',
            };

            return response()->json([
                'success' => false,
                'message' => $message,
            ], 401);
        }

        $user = $result['user'];
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
        ]);
    }

    /**
     * Register new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->register($request->validated());

            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الحساب بنجاح',
                'data' => [
                    'user' => new UserResource($user),
                    'token' => $token,
                ],
            ], 201);
        } catch (\Exception $e) {
            // Return the specific error message from the service
            // This will be a clear, user-friendly message explaining the exact reason for failure
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new UserResource($request->user()),
        ]);
    }

    /**
     * Logout user.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // 1. Delete FCM token for this device if provided
        if ($request->has('device_token')) {
            \App\Models\DeviceToken::where('user_id', $user->id)
                ->where('device_token', $request->device_token)
                ->delete();
        }

        // 2. Delete current access token
        $user->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);
    }
}

