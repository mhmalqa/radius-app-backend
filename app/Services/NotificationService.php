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
        return DB::transaction(function () use ($data, $userIds, $targetType) {
            $notification = Notification::create([
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

            // Send based on target type
            match ($targetType) {
                'specific' => $this->sendToUsers($notification, $userIds ?? []),
                'active' => $this->sendToActiveUsers($notification),
                default => $this->sendToAllUsers($notification),
            };

            return $notification;
        });
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

            // Send push notification
            $this->sendPushNotification($notification, $user);
        } catch (\Exception $e) {
            Log::error('Failed to attach notification to user', [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send push notification to user.
     */
    protected function sendPushNotification(Notification $notification, AppUser $user): void
    {
        // TODO: Implement push notification sending
        // This would integrate with FCM, APNS, or similar service
        
        try {
            // Get active device tokens
            $deviceTokens = $user->deviceTokens()->where('is_active', true)->get();

            foreach ($deviceTokens as $deviceToken) {
                // Send push notification
                // Example: FCM::send($deviceToken->device_token, $notification);
                
                // Mark as sent
                $notification->users()->updateExistingPivot($user->id, [
                    'is_sent' => true,
                    'sent_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send push notification', [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
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

