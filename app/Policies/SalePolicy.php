<?php

namespace App\Policies;

use App\Models\User;

class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageFinance();
    }

    public function view(User $user, $sale): bool
    {
        return $user->canManageFinance();
    }

    public function create(User $user): bool
    {
        return $user->canManageFinance();
    }

    public function void(User $user, $sale): bool
    {
        return $user->canManageFinance();
    }
}
