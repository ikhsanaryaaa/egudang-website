<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    /**
     * Determine whether the user can view any audit logs.
     * Audit log visibility is controlled by permission.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->hasPermissionTo('view audit logs');
    }

    /**
     * Determine whether the user can view a specific audit log.
     */
    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->hasRole('Super Admin') || $user->hasPermissionTo('view audit logs');
    }

    /**
     * Audit logs are immutable — no one can create via UI.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Audit logs are immutable — no one can update.
     */
    public function update(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    /**
     * Audit logs are immutable — no one can delete.
     */
    public function delete(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    /**
     * Audit logs are immutable — no one can restore.
     */
    public function restore(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    /**
     * Audit logs are immutable — no one can force delete.
     */
    public function forceDelete(User $user, AuditLog $auditLog): bool
    {
        return false;
    }
}
