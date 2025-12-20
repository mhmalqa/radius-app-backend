<?php

namespace App\Policies;

use App\Models\AppUser;
use App\Models\PaymentRequest;

class PaymentRequestPolicy
{
    /**
     * Determine if user can view any payment requests.
     */
    public function viewAny(AppUser $user): bool
    {
        return $user->isAdmin() || $user->isAccountant();
    }

    /**
     * Determine if user can view the payment request.
     */
    public function view(AppUser $user, PaymentRequest $paymentRequest): bool
    {
        return $user->isAdmin() || $user->isAccountant() || $paymentRequest->user_id === $user->id;
    }

    /**
     * Determine if user can update the payment request.
     */
    public function update(AppUser $user, PaymentRequest $paymentRequest): bool
    {
        return $user->isAdmin() || $user->isAccountant();
    }

    /**
     * Determine if user can create a payment request.
     */
    public function create(AppUser $user): bool
    {
        // Regular users can create their own payment requests
        // Admin/Accountant can create cash payments
        return true;
    }

    /**
     * Determine if user can delete the payment request.
     */
    public function delete(AppUser $user, PaymentRequest $paymentRequest): bool
    {
        return $user->isAdmin();
    }
}

