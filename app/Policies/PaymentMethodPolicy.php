<?php

namespace App\Policies;

use App\Models\AppUser;
use App\Models\PaymentMethod;

class PaymentMethodPolicy
{
    /**
     * Determine if user can view any payment methods.
     */
    public function viewAny(AppUser $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can create payment methods.
     */
    public function create(AppUser $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can update payment methods.
     */
    public function update(AppUser $user, PaymentMethod $paymentMethod): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can delete payment methods.
     */
    public function delete(AppUser $user, PaymentMethod $paymentMethod): bool
    {
        return $user->isAdmin();
    }
}

