<?php

namespace App\Policies;

use App\Models\AppUser;
use App\Models\MaintenanceRequest;
use Illuminate\Auth\Access\Response;

class MaintenanceRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(AppUser $appUser): bool
    {
        return $appUser->isAdmin() || $appUser->isAccountant();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(AppUser $appUser, MaintenanceRequest $maintenanceRequest): bool
    {
        return $appUser->isAdmin() || 
               $appUser->isAccountant() || 
               $maintenanceRequest->user_id === $appUser->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(AppUser $appUser): bool
    {
        return true; // All authenticated users can create maintenance requests
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(AppUser $appUser, MaintenanceRequest $maintenanceRequest): bool
    {
        return $appUser->isAdmin() || $appUser->isAccountant();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(AppUser $appUser, MaintenanceRequest $maintenanceRequest): bool
    {
        return $appUser->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(AppUser $appUser, MaintenanceRequest $maintenanceRequest): bool
    {
        return $appUser->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(AppUser $appUser, MaintenanceRequest $maintenanceRequest): bool
    {
        return $appUser->isAdmin();
    }
}
