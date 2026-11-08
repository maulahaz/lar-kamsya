<?php

namespace App\Policies;

use App\Models\Goat;
use App\Models\User;

class GoatPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Goat $goat): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->canManageFinance();
    }

    public function update(User $user, Goat $goat): bool
    {
        return $user->canManageFinance();
    }

    public function delete(User $user, Goat $goat): bool
    {
        return $user->canManageFinance();
    }
}
