<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserManagement\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\AppUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserManagementController extends Controller
{
    /**
     * Get all users with filtering and search.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AppUser::class);

        $query = AppUser::query();

        // Search by username, firstname, phone, or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('firstname', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Filter by is_active
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by live_access
        if ($request->has('live_access')) {
            $query->where('live_access', $request->boolean('live_access'));
        }

        // Filter by language
        if ($request->has('language')) {
            $query->where('language', $request->language);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $users = $query->with('subscription')->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($users->items()),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Get single user details.
     */
    public function show(AppUser $user): JsonResponse
    {
        $this->authorize('view', $user);

        $user->load(['subscription', 'paymentRequests', 'deviceTokens']);

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Update user (admin only).
     */
    public function update(UpdateUserRequest $request, AppUser $user): JsonResponse
    {
        $this->authorize('update', $user);

        DB::transaction(function () use ($request, $user) {
            $data = $request->validated();

            // Handle password update
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            // Handle username change (if different from current)
            if (isset($data['username']) && $data['username'] !== $user->username) {
                // Update radius_username in subscription if exists
                if ($user->subscription) {
                    $user->subscription->update([
                        'radius_username' => $data['username'],
                    ]);
                }
            }

            $user->update($data);
        });

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المستخدم بنجاح',
            'data' => new UserResource($user->fresh()->load('subscription')),
        ]);
    }

    /**
     * Toggle user active status.
     */
    public function toggleActive(AppUser $user): JsonResponse
    {
        $this->authorize('update', $user);

        $user->update([
            'is_active' => !$user->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => $user->is_active ? 'تم تفعيل الحساب' : 'تم تعطيل الحساب',
            'data' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Toggle live access.
     */
    public function toggleLiveAccess(AppUser $user): JsonResponse
    {
        $this->authorize('update', $user);

        $user->update([
            'live_access' => !$user->live_access,
        ]);

        return response()->json([
            'success' => true,
            'message' => $user->live_access ? 'تم تفعيل صلاحية البث' : 'تم إلغاء صلاحية البث',
            'data' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Change user role.
     */
    public function changeRole(Request $request, AppUser $user): JsonResponse
    {
        $this->authorize('update', $user);

        $request->validate([
            'role' => ['required', 'integer', 'in:0,1,2'],
        ]);

        $user->update([
            'role' => $request->role,
        ]);

        $roleLabels = ['مستخدم', 'محاسب', 'مدير'];

        return response()->json([
            'success' => true,
            'message' => "تم تغيير الدور إلى: {$roleLabels[$request->role]}",
            'data' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Delete user.
     *
     * ملاحظة مهمة:
     * بدلاً من الحذف الفعلي للمستخدم (والذي قد يؤدي إلى حذف كل السجلات المرتبطة به
     * مثل الإيرادات، طلبات الدفع، طلبات الصيانة، إلخ بسبب onDelete('cascade')),
     * نقوم هنا بتعطيل الحساب فقط والإبقاء على جميع السجلات التاريخية كما هي.
     */
    public function destroy(AppUser $user): JsonResponse
    {
        $this->authorize('delete', $user);

        // لا نقوم بحذف المستخدم فعلياً حتى لا يتم حذف أي من العمليات المرتبطة به
        // مثل الإيرادات، طلبات الدفع، طلبات الصيانة، إلخ.
        // بدلاً من ذلك نقوم بتعطيل الحساب مع إمكانية استخدامه كمرجع في السجلات القديمة.
        $user->update([
            'is_active' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تعطيل المستخدم بنجاح، ولم يتم حذف أي من عملياته أو سجلاته السابقة',
        ]);
    }
}

