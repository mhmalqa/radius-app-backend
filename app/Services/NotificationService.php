<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\AppUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Create and send notification to users.
     * 
     * @param array $data Notification data
     * @param array|null $userIds Specific user IDs (optional)
     * @param string $targetType Target type: 'all', 'active', 'specific' (default: 'all')
     */
    public function createNotification(array $data, ?array $userIds = null, string $targetType = 'all'): Notification
    {
        Log::info('Creating notification', [
            'title' => $data['title'] ?? 'N/A',
            'type' => $data['type'] ?? 'manual',
            'target_type' => $targetType,
            'user_ids_count' => $userIds ? count($userIds) : 0,
        ]);

        $notification = DB::transaction(function () use ($data) {
            return Notification::create([
                'title' => $data['title'],
                'body' => $data['body'],
                'type' => $data['type'] ?? 'manual',
                'priority' => $data['priority'] ?? 0,
                'action_url' => $data['action_url'] ?? null,
                'action_text' => $data['action_text'] ?? null,
                'icon' => $data['icon'] ?? null,
                'sound' => $data['sound'] ?? null,
                'badge' => $data['badge'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);
        });

        Log::info('Notification created, starting send process', [
            'notification_id' => $notification->id,
            'target_type' => $targetType,
        ]);

        // Send based on target type (outside transaction to avoid blocking)
        try {
            match ($targetType) {
                'specific' => $this->sendToUsers($notification, $userIds ?? []),
                'active' => $this->sendToActiveUsers($notification),
                default => $this->sendToAllUsers($notification),
            };
        } catch (\Exception $e) {
            Log::error('Failed to send notifications', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Don't throw - notification is already created
        }

        return $notification;
    }

    /**
     * Send notification to active users only (with active subscription).
     */
    public function sendToActiveUsers(Notification $notification): void
    {
        $users = AppUser::where('is_active', true)
            ->whereHas('subscription', function ($query) {
                $query->where('expiration_at', '>', now());
            })
            ->get();

        Log::info('Sending notification to active users', [
            'notification_id' => $notification->id,
            'users_count' => $users->count(),
        ]);

        foreach ($users as $user) {
            $this->attachNotificationToUser($notification, $user);
        }
    }

    /**
     * Send notification to specific users.
     */
    public function sendToUsers(Notification $notification, array $userIds): void
    {
        $users = AppUser::whereIn('id', $userIds)
            ->where('is_active', true)
            ->get();

        Log::info('Sending notification to specific users', [
            'notification_id' => $notification->id,
            'requested_user_ids' => $userIds,
            'found_users_count' => $users->count(),
        ]);

        foreach ($users as $user) {
            $this->attachNotificationToUser($notification, $user);
        }
    }

    /**
     * Send notification to all active users.
     */
    public function sendToAllUsers(Notification $notification): void
    {
        $users = AppUser::where('is_active', true)->get();

        Log::info('Sending notification to all active users', [
            'notification_id' => $notification->id,
            'users_count' => $users->count(),
        ]);

        foreach ($users as $user) {
            $this->attachNotificationToUser($notification, $user);
        }
    }

    /**
     * Attach notification to user and send push notification.
     */
    protected function attachNotificationToUser(Notification $notification, AppUser $user): void
    {
        try {
            $notification->users()->attach($user->id, [
                'is_read' => false,
                'is_sent' => false,
            ]);

            Log::info('Notification attached to user, sending push notification', [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'title' => $notification->title,
            ]);

            // Send push notification
            $this->sendPushNotification($notification, $user);
        } catch (\Exception $e) {
            Log::error('Failed to attach notification to user', [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Send push notification to user.
     */
    protected function sendPushNotification(Notification $notification, AppUser $user): void
    {
        try {
            Log::info('Starting push notification send', [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'title' => $notification->title,
            ]);

            // Get active device tokens
            $deviceTokens = $user->deviceTokens()->where('is_active', true)->get();

            Log::info('Device tokens retrieved', [
                'user_id' => $user->id,
                'notification_id' => $notification->id,
                'tokens_count' => $deviceTokens->count(),
            ]);

            if ($deviceTokens->isEmpty()) {
                Log::warning('No active device tokens for user - notification will not be sent via FCM', [
                    'user_id' => $user->id,
                    'notification_id' => $notification->id,
                    'username' => $user->username,
                ]);
                return;
            }

            $fcmService = app(\App\Services\FirebaseMessagingService::class);
            
            Log::info('FCM service initialized, preparing notification data', [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
            ]);

            // Prepare notification data
            $notificationData = [
                'title' => $notification->title,
                'body' => $notification->body,
                'sound' => $notification->sound ?? 'default',
                'badge' => $notification->badge,
                'icon' => $notification->icon,
            ];

            // Prepare data payload
            $data = [
                'notification_id' => (string) $notification->id,
                'type' => $notification->type,
                'priority' => (string) $notification->priority,
                'action_url' => $notification->action_url ?? '',
                'action_text' => $notification->action_text ?? '',
            ];

            // Send to all device tokens
            $tokens = $deviceTokens->pluck('device_token')->toArray();
            
            Log::info('Sending FCM notification to devices', [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'tokens_count' => count($tokens),
                'tokens_preview' => array_map(fn($t) => substr($t, 0, 20) . '...', array_slice($tokens, 0, 3)),
            ]);
            
            $results = $fcmService->sendToDevices($tokens, $notificationData, $data);

            Log::info('FCM send completed, processing results', [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'results_count' => count($results),
                'results' => $results,
            ]);

            // Update sent status - check if all results are true
            // $results is an array with tokens as keys and boolean as values
            $allSent = !empty($results) && !in_array(false, array_values($results), true);
            $notification->users()->updateExistingPivot($user->id, [
                'is_sent' => $allSent,
                'sent_at' => now(),
            ]);

            if (!$allSent) {
                $failedTokens = array_keys(array_filter($results, fn($result) => $result === false));
                Log::warning('Some FCM notifications failed', [
                    'user_id' => $user->id,
                    'notification_id' => $notification->id,
                    'failed_tokens_count' => count($failedTokens),
                    'total_tokens' => count($tokens),
                ]);
            } else {
                Log::info('All FCM notifications sent successfully', [
                    'user_id' => $user->id,
                    'notification_id' => $notification->id,
                    'tokens_count' => count($tokens),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send push notification', [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Mark error
            $notification->users()->updateExistingPivot($user->id, [
                'send_error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(Notification $notification, AppUser $user): void
    {
        $notification->users()->updateExistingPivot($user->id, [
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Get user notifications.
     */
    public function getUserNotifications(AppUser $user, ?bool $unreadOnly = false): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $user->notifications()
            ->with('creator')
            ->orderBy('notifications.created_at', 'desc');

        if ($unreadOnly) {
            $query->wherePivot('is_read', false);
        }

        return $query->paginate(15);
    }

    /**
     * Get unread notifications count.
     */
    public function getUnreadCount(AppUser $user): int
    {
        return $user->notifications()
            ->wherePivot('is_read', false)
            ->count();
    }

    /**
     * Send notification to admins only.
     */
    public function sendToAdmins(array $data): Notification
    {
        $notification = Notification::create([
            'title' => $data['title'],
            'body' => $data['body'],
            'type' => $data['type'] ?? 'system',
            'priority' => $data['priority'] ?? 1,
            'action_url' => $data['action_url'] ?? null,
            'action_text' => $data['action_text'] ?? null,
            'icon' => $data['icon'] ?? null,
            'sound' => $data['sound'] ?? null,
            'badge' => $data['badge'] ?? null,
            'created_by' => $data['created_by'] ?? null,
        ]);

        $admins = AppUser::where('role', 2) // Admin role
            ->where('is_active', true)
            ->get();

        foreach ($admins as $admin) {
            $this->attachNotificationToUser($notification, $admin);
        }

        return $notification;
    }

    /**
     * Send notification to accountants only.
     */
    public function sendToAccountants(array $data): Notification
    {
        $notification = Notification::create([
            'title' => $data['title'],
            'body' => $data['body'],
            'type' => $data['type'] ?? 'system',
            'priority' => $data['priority'] ?? 1,
            'action_url' => $data['action_url'] ?? null,
            'action_text' => $data['action_text'] ?? null,
            'icon' => $data['icon'] ?? null,
            'sound' => $data['sound'] ?? null,
            'badge' => $data['badge'] ?? null,
            'created_by' => $data['created_by'] ?? null,
        ]);

        $accountants = AppUser::where('role', 1) // Accountant role
            ->where('is_active', true)
            ->get();

        foreach ($accountants as $accountant) {
            $this->attachNotificationToUser($notification, $accountant);
        }

        return $notification;
    }

    /**
     * Send notification to admins and accountants.
     */
    public function sendToAdminsAndAccountants(array $data): Notification
    {
        $notification = Notification::create([
            'title' => $data['title'],
            'body' => $data['body'],
            'type' => $data['type'] ?? 'system',
            'priority' => $data['priority'] ?? 1,
            'action_url' => $data['action_url'] ?? null,
            'action_text' => $data['action_text'] ?? null,
            'icon' => $data['icon'] ?? null,
            'sound' => $data['sound'] ?? null,
            'badge' => $data['badge'] ?? null,
            'created_by' => $data['created_by'] ?? null,
        ]);

        $staff = AppUser::whereIn('role', [1, 2]) // Accountant and Admin
            ->where('is_active', true)
            ->get();

        foreach ($staff as $user) {
            $this->attachNotificationToUser($notification, $user);
        }

        return $notification;
    }
}

