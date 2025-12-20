<?php

namespace App\Policies;

use App\Models\AppUser;
use App\Models\Slide;

class SlidePolicy
{
    /**
     * Determine if user can view any slides.
     */
    public function viewAny(AppUser $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can create slides.
     */
    public function create(AppUser $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can update the slide.
     */
    public function update(AppUser $user, Slide $slide): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can delete the slide.
     */
    public function delete(AppUser $user, Slide $slide): bool
    {
        return $user->isAdmin();
    }
}

