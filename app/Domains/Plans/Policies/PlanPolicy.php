<?php

namespace App\Domains\Plans\Policies;

use App\Domains\Plans\Models\Plan;
use App\Models\User;

class PlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'super-admin']);
    }

    public function view(User $user, Plan $plan): bool
    {
        return $user->hasRole(['admin', 'super-admin']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'super-admin']);
    }

    public function update(User $user, Plan $plan): bool
    {
        return $user->hasRole(['admin', 'super-admin']);
    }

    public function delete(User $user, Plan $plan): bool
    {
        return $user->hasRole('super-admin');
    }
}
