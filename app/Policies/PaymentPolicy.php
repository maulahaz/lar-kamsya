<?php

namespace App\Policies;

use App\Models\User;

class PaymentPolicy
{
    public function create(User $user): bool
    {
        return $user->canManageFinance();
    }

    public function void(User $user, $payment): bool
    {
        return $user->canManageFinance();
    }
}
