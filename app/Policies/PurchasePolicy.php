<?php

namespace App\Policies;

use App\Models\User;

class PurchasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageFinance();
    }

    public function view(User $user, $purchase): bool
    {
        return $user->canManageFinance();
    }

    public function create(User $user): bool
    {
        return $user->canManageFinance();
    }

    public function void(User $user, $purchase): bool
    {
        return $user->canManageFinance();
    }
}
