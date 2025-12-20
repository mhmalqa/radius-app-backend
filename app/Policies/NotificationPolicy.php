<?php

namespace App\Policies;

use App\Models\AppUser;
use App\Models\Notification;

class NotificationPolicy
{
    /**
     * Determine if user can create notifications.
     */
    public function create(AppUser $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can update the notification.
     */
    public function update(AppUser $user, Notification $notification): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can delete the notification.
     */
    public function delete(AppUser $user, Notification $notification): bool
    {
        return $user->isAdmin();
    }
}

