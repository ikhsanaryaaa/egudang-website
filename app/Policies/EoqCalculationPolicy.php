<?php

namespace App\Policies;

use App\Models\EoqCalculation;
use App\Models\User;

class EoqCalculationPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view eoq calculations');
    }

    public function view(User $user, EoqCalculation $eoqCalculation): bool
    {
        return $user->hasPermissionTo('view eoq calculations');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create eoq calculations');
    }

    public function update(User $user, EoqCalculation $eoqCalculation): bool
    {
        return $user->hasPermissionTo('edit eoq calculations');
    }

    public function delete(User $user, EoqCalculation $eoqCalculation): bool
    {
        return $user->hasPermissionTo('delete eoq calculations');
    }

    public function restore(User $user, EoqCalculation $eoqCalculation): bool
    {
        return $user->hasPermissionTo('delete eoq calculations');
    }

    public function forceDelete(User $user, EoqCalculation $eoqCalculation): bool
    {
        return $user->hasPermissionTo('delete eoq calculations');
    }
}
