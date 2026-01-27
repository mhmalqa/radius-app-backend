<?php

namespace App\Http\Controllers;

use App\Http\Requests\Notification\CreateNotificationRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Get user notifications with filtering.
     * 
     * Query parameters:
     * - status: 'all', 'read', 'unread' (default: 'all')
     * - type: 'system', 'manual' or null for all types (default: null)
     * - search: search in title and body (default: null)
     * - page: page number for pagination (default: 1)
     * - per_page: items per page (default: 15)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Get filter parameters
        $status = $request->input('status', 'all'); // 'all', 'read', 'unread'
        $type = $request->input('type'); // 'system', 'manual', or null for all
        $search = $request->input('search'); // search in title and body
        $perPage = $request->integer('per_page', 15);

        // Validate status
        if (!in_array($status, ['all', 'read', 'unread'])) {
            $status = 'all';
        }

        // Validate type
        if ($type && !in_array($type, ['system', 'manual'])) {
            $type = null;
        }

        $notifications = $this->notificationService->getUserNotifications(
            $user,
            $status,
            $type,
            $perPage,
            $search
        );

        return response()->json([
            'success' => true,
            'data' => NotificationResource::collection($notifications->items()),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'unread_count' => $this->notificationService->getUnreadCount($user),
                'read_count' => $this->notificationService->getReadCount($user),
                'filters' => [
                    'status' => $status,
                    'type' => $type ?? 'all',
                    'search' => $search ?? '',
                ],
            ],
        ]);
    }

    /**
     * Get unread notifications count.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->notificationService->getUnreadCount($request->user());

        return response()->json([
            'success' => true,
            'data' => [
                'count' => $count,
            ],
        ]);
    }

    /**
     * Get unread notifications with pagination.
     */
    public function unread(Request $request): JsonResponse
    {
        $user = $request->user();
        $notifications = $this->notificationService->getUnreadNotifications($user);
        $unreadCount = $this->notificationService->getUnreadCount($user);
        $readCount = $this->notificationService->getReadCount($user);

        return response()->json([
            'success' => true,
            'data' => NotificationResource::collection($notifications->items()),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'unread_count' => $unreadCount,
                'read_count' => $readCount,
            ],
        ]);
    }

    /**
     * Get read notifications with pagination.
     */
    public function read(Request $request): JsonResponse
    {
        $user = $request->user();
        $notifications = $this->notificationService->getReadNotifications($user);
        $unreadCount = $this->notificationService->getUnreadCount($user);
        $readCount = $this->notificationService->getReadCount($user);

        return response()->json([
            'success' => true,
            'data' => NotificationResource::collection($notifications->items()),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'unread_count' => $unreadCount,
                'read_count' => $readCount,
            ],
        ]);
    }

    /**
     * Get read notifications count.
     */
    public function readCount(Request $request): JsonResponse
    {
        $count = $this->notificationService->getReadCount($request->user());

        return response()->json([
            'success' => true,
            'data' => [
                'count' => $count,
            ],
        ]);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        $this->notificationService->markAsRead($notification, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'تم تحديد الإشعار كمقروء',
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $unreadNotifications = $user->notifications()
            ->wherePivot('is_read', false)
            ->get();

        foreach ($unreadNotifications as $notification) {
            $this->notificationService->markAsRead($notification, $user);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديد جميع الإشعارات كمقروءة',
        ]);
    }

    /**
     * Create new notification (admin only).
     */
    public function store(CreateNotificationRequest $request): JsonResponse
    {
        $this->authorize('create', Notification::class);

        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $targetType = $request->input('target_type', 'all');
        $userIds = $request->input('user_ids');

        $notification = $this->notificationService->createNotification(
            $data,
            $userIds,
            $targetType
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الإشعار بنجاح',
            'data' => new NotificationResource($notification->load('creator')),
        ], 201);
    }
}

