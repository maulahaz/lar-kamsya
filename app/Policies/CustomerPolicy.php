<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageFinance();
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->canManageFinance();
    }

    public function create(User $user): bool
    {
        return $user->canManageFinance();
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->canManageFinance();
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->canManageFinance();
    }
}
