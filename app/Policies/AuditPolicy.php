<?php

namespace App\Policies;

use App\Models\Audit;
use App\Models\User;

class AuditPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-audit');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Audit $audit): bool
    {
        // All authenticated users with view-audit permission can view audits
        return $user->hasPermissionTo('view-audit');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Organization Owner and Audit Manager can create audits
        return $user->hasPermissionTo('create-audit');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Audit $audit): bool
    {
        // Organization Owner and Audit Manager can update audits
        return $user->hasPermissionTo('update-audit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Audit $audit): bool
    {
        // Only Organization Owner can delete audits
        return $user->hasPermissionTo('delete-audit') && $user->hasRole('Organization Owner');
    }

    /**
     * Determine whether the user can close the audit.
     */
    public function close(User $user, Audit $audit): bool
    {
        // Organization Owner and Audit Manager can close audits
        return $user->hasPermissionTo('close-audit');
    }

    /**
     * Determine whether the user can export the audit.
     */
    public function export(User $user, Audit $audit): bool
    {
        // Organization Owner and Audit Manager can export audits
        return $user->hasPermissionTo('export-audit');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Audit $audit): bool
    {
        // Only Organization Owner can restore
        return $user->hasRole('Organization Owner');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Audit $audit): bool
    {
        // Only Organization Owner can permanently delete
        return $user->hasRole('Organization Owner');
    }
}
