<?php

namespace App\Policies;

use App\Models\StockTransaction;
use App\Models\User;

class StockTransactionPolicy
{
    /**
     * Super Admin can perform every stock transaction action exposed by the system.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view stock movements');
    }

    public function view(User $user, StockTransaction $stockTransaction): bool
    {
        return $user->hasPermissionTo('view stock movements');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create stock movements');
    }

    public function update(User $user, StockTransaction $stockTransaction): bool
    {
        return $user->hasPermissionTo('edit stock movements');
    }

    public function delete(User $user, StockTransaction $stockTransaction): bool
    {
        return $user->hasPermissionTo('delete stock movements');
    }

    public function restore(User $user, StockTransaction $stockTransaction): bool
    {
        return $user->hasPermissionTo('delete stock movements');
    }

    public function forceDelete(User $user, StockTransaction $stockTransaction): bool
    {
        return $user->hasPermissionTo('delete stock movements');
    }
}
