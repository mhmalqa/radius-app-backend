<?php

namespace App\Policies;

use App\Models\AppUser;

class UserPolicy
{
    /**
     * Determine if user can view any users.
     */
    public function viewAny(AppUser $user): bool
    {
        return $user->isAdmin() || $user->isAccountant();
    }

    /**
     * Determine if user can view the user.
     */
    public function view(AppUser $user, AppUser $model): bool
    {
        return $user->isAdmin() || $user->isAccountant() || $user->id === $model->id;
    }

    /**
     * Determine if user can update the user.
     */
    public function update(AppUser $user, AppUser $model): bool
    {
        // Admin can update anyone
        if ($user->isAdmin()) {
            return true;
        }

        // Accountant can update regular users only
        if ($user->isAccountant() && $model->isUser()) {
            return true;
        }

        // Users can only update themselves
        return $user->id === $model->id;
    }

    /**
     * Determine if user can delete the user.
     */
    public function delete(AppUser $user, AppUser $model): bool
    {
        return $user->isAdmin();
    }
}

