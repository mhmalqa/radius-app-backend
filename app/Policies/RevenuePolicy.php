<?php

namespace App\Policies;

use App\Models\AppUser;
use App\Models\Revenue;

class RevenuePolicy
{
    /**
     * Determine if the user can view any revenues.
     */
    public function viewAny(AppUser $user): bool
    {
        return $user->isAdmin() || $user->isAccountant();
    }

    /**
     * Determine if the user can view the revenue.
     */
    public function view(AppUser $user, Revenue $revenue): bool
    {
        // Admin and accountant can view all revenues
        if ($user->isAdmin() || $user->isAccountant()) {
            return true;
        }

        // Users can only view their own revenues
        return $user->id === $revenue->user_id;
    }

    /**
     * Determine if the user can create revenues.
     */
    public function create(AppUser $user): bool
    {
        // Revenues are created automatically, not manually
        return false;
    }

    /**
     * Determine if the user can update the revenue.
     */
    public function update(AppUser $user, Revenue $revenue): bool
    {
        // Revenues should not be updated manually
        return false;
    }

    /**
     * Determine if the user can delete the revenue.
     */
    public function delete(AppUser $user, Revenue $revenue): bool
    {
        // Only admin can delete revenues
        return $user->isAdmin();
    }
}

