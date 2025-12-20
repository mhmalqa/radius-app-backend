<?php

namespace App\Policies;

use App\Models\AppSetting;
use App\Models\AppUser;
use Illuminate\Auth\Access\Response;

class AppSettingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(AppUser $appUser): bool
    {
        return true; // All authenticated users can view settings
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(AppUser $appUser, AppSetting $appSetting): bool
    {
        return true; // All authenticated users can view settings
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(AppUser $appUser): bool
    {
        return $appUser->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(AppUser $appUser, AppSetting $appSetting): bool
    {
        return $appUser->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(AppUser $appUser, AppSetting $appSetting): bool
    {
        return $appUser->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(AppUser $appUser, AppSetting $appSetting): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(AppUser $appUser, AppSetting $appSetting): bool
    {
        return false;
    }
}
