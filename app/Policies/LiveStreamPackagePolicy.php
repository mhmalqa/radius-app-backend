<?php

namespace App\Policies;

use App\Models\AppUser;
use App\Models\LiveStreamPackage;

class LiveStreamPackagePolicy
{
    public function viewAny(AppUser $user): bool
    {
        return $user->isAdmin();
    }

    public function create(AppUser $user): bool
    {
        return $user->isAdmin();
    }

    public function update(AppUser $user, LiveStreamPackage $package): bool
    {
        return $user->isAdmin();
    }

    public function delete(AppUser $user, LiveStreamPackage $package): bool
    {
        return $user->isAdmin();
    }
}

