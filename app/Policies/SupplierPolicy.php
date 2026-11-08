<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageFinance();
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->canManageFinance();
    }

    public function create(User $user): bool
    {
        return $user->canManageFinance();
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->canManageFinance();
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->canManageFinance();
    }
}
