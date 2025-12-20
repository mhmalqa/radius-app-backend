<?php

namespace App\Http\Controllers;

use App\Http\Requests\MaintenanceRequest\CreateMaintenanceRequestRequest;
use App\Http\Requests\MaintenanceRequest\UpdateMaintenanceRequestStatusRequest;
use App\Http\Resources\MaintenanceRequestResource;
use App\Models\MaintenanceRequest;
use App\Services\RadiusSyncService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceRequestController extends Controller
{
    public function __construct(
        protected RadiusSyncService $radiusSyncService,
        protected NotificationService $notificationService
    ) {}

    /**
     * Get user's maintenance requests.
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');
        
        $query = MaintenanceRequest::where('user_id', $request->user()->id)
            ->with(['assignedTo'])
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        $requests = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => MaintenanceRequestResource::collection($requests->items()),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
        ]);
    }

    /**
     * Create new maintenance request.
     */
    public function store(CreateMaintenanceRequestRequest $request): JsonResponse
    {
        $user = $request->user();

        // Get subscription data from Radius
        $subscriptionData = $this->radiusSyncService->getUserDataFromRadius($user->username);

        if (!$subscriptionData) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب بيانات الاشتراك من الراديوس. يرجى المحاولة مرة أخرى',
            ], 500);
        }

        $maintenanceRequest = MaintenanceRequest::create([
            'user_id' => $user->id,
            'address' => $request->address,
            'description' => $request->description,
            'subscription_data' => $subscriptionData,
            'status' => 'pending', // قيد الانتظار
        ]);

        // إرسال إشعار للمديرين والمحاسبين عند إنشاء طلب صيانة جديد
        $this->notificationService->sendToAdminsAndAccountants([
            'title' => 'طلب صيانة جديد',
            'body' => "تم إرسال طلب صيانة جديد من المستخدم {$user->username}",
            'type' => 'system',
            'priority' => 1,
            'action_url' => "/admin/maintenance-requests/{$maintenanceRequest->id}",
            'action_text' => 'عرض الطلب',
            'icon' => 'build',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال طلب الصيانة بنجاح',
            'data' => new MaintenanceRequestResource($maintenanceRequest->load(['user', 'assignedTo'])),
        ], 201);
    }

    /**
     * Get single maintenance request.
     */
    public function show(Request $request, MaintenanceRequest $maintenanceRequest): JsonResponse
    {
        // Check if user owns this request or is admin/accountant
        if ($maintenanceRequest->user_id !== $request->user()->id && 
            !$request->user()->isAdmin() && 
            !$request->user()->isAccountant()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالوصول إلى هذا الطلب',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new MaintenanceRequestResource($maintenanceRequest->load(['user', 'assignedTo'])),
        ]);
    }

    /**
     * Get all maintenance requests (admin/accountant only).
     */
    public function all(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MaintenanceRequest::class);

        $status = $request->query('status');
        $userId = $request->query('user_id');

        $query = MaintenanceRequest::with(['user', 'assignedTo'])
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $requests = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => MaintenanceRequestResource::collection($requests->items()),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
        ]);
    }

    /**
     * Update maintenance request status (admin/accountant only).
     */
    public function updateStatus(
        Request $request, 
        MaintenanceRequest $maintenanceRequest, 
        UpdateMaintenanceRequestStatusRequest $updateRequest
    ): JsonResponse {
        $this->authorize('update', $maintenanceRequest);

        $validated = $updateRequest->validated();

        $updateData = [
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? $maintenanceRequest->notes,
        ];

        // If status changed to submitted, update it
        if ($validated['status'] === 'submitted') {
            $updateData['status'] = 'submitted';
        }

        // If status changed to completed, set completed_at
        if ($validated['status'] === 'completed' && !$maintenanceRequest->completed_at) {
            $updateData['completed_at'] = now();
        }

        // If assigned_to is provided, update it
        if (isset($validated['assigned_to'])) {
            $updateData['assigned_to'] = $validated['assigned_to'];
        }

        $oldStatus = $maintenanceRequest->status;
        $maintenanceRequest->update($updateData);
        $maintenanceRequest->refresh();

        // إرسال إشعارات عند تغيير الحالة
        if ($oldStatus !== $validated['status']) {
            $statusLabels = [
                'pending' => 'قيد الانتظار',
                'submitted' => 'تم الإرسال',
                'in_progress' => 'قيد التنفيذ',
                'completed' => 'مكتمل',
                'cancelled' => 'ملغي',
            ];

            $statusLabel = $statusLabels[$validated['status']] ?? $validated['status'];

            // إشعار للمستخدم
            $this->notificationService->createNotification([
                'title' => 'تحديث حالة طلب الصيانة',
                'body' => "تم تحديث حالة طلب الصيانة الخاص بك إلى: {$statusLabel}",
                'type' => 'system',
                'priority' => 1,
                'action_url' => "/maintenance-requests/{$maintenanceRequest->id}",
                'action_text' => 'عرض الطلب',
                'icon' => 'info',
            ], [$maintenanceRequest->user_id], 'specific');

            // إشعار للمديرين عند إكمال الطلب
            if ($validated['status'] === 'completed') {
                $this->notificationService->sendToAdmins([
                    'title' => 'تم إكمال طلب صيانة',
                    'body' => "تم إكمال طلب الصيانة للمستخدم {$maintenanceRequest->user->username}",
                    'type' => 'system',
                    'priority' => 1,
                    'action_url' => "/admin/maintenance-requests/{$maintenanceRequest->id}",
                    'action_text' => 'عرض الطلب',
                    'icon' => 'check_circle',
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة الطلب بنجاح',
            'data' => new MaintenanceRequestResource($maintenanceRequest->fresh()->load(['user', 'assignedTo'])),
        ]);
    }
}
