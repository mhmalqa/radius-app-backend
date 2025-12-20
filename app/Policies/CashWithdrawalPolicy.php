<?php

namespace App\Policies;

use App\Models\AppUser;
use App\Models\CashWithdrawal;

class CashWithdrawalPolicy
{
    /**
     * Determine if user can view any cash withdrawals.
     */
    public function viewAny(AppUser $user): bool
    {
        return $user->isAdmin() || $user->isAccountant();
    }

    /**
     * Determine if user can view the cash withdrawal.
     */
    public function view(AppUser $user, CashWithdrawal $cashWithdrawal): bool
    {
        return $user->isAdmin() || $user->isAccountant();
    }

    /**
     * Determine if user can create cash withdrawals.
     */
    public function create(AppUser $user): bool
    {
        return $user->isAdmin() || $user->isAccountant();
    }

    /**
     * Determine if user can update the cash withdrawal.
     */
    public function update(AppUser $user, CashWithdrawal $cashWithdrawal): bool
    {
        return $user->isAdmin() || $user->isAccountant();
    }

    /**
     * Determine if user can delete the cash withdrawal.
     */
    public function delete(AppUser $user, CashWithdrawal $cashWithdrawal): bool
    {
        return $user->isAdmin(); // Only admin can delete
    }
}

