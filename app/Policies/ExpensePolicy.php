<?php

namespace App\Policies;

use App\Models\User;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageFinance();
    }

    public function create(User $user): bool
    {
        return $user->canManageFinance();
    }

    public function void(User $user, $expense): bool
    {
        return $user->canManageFinance();
    }
}
