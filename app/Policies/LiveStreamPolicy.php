<?php

namespace App\Policies;

use App\Models\AppUser;
use App\Models\LiveStream;

class LiveStreamPolicy
{
    /**
     * Determine if user can view any live streams.
     */
    public function viewAny(AppUser $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can create live streams.
     */
    public function create(AppUser $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can update the live stream.
     */
    public function update(AppUser $user, LiveStream $liveStream): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can delete the live stream.
     */
    public function delete(AppUser $user, LiveStream $liveStream): bool
    {
        return $user->isAdmin();
    }
}

