<?php

namespace App\Policies;

use App\Models\Evidence;
use App\Models\User;

class EvidencePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Organization Owner, Audit Manager, Contributor, Viewer can view evidence
        return $user->hasAnyPermission(['view-evidence', 'view-all-evidence', 'view-own-evidence']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Evidence $evidence): bool
    {
        // Organization Owner and Audit Manager can view all evidence
        if ($user->hasPermissionTo('view-all-evidence')) {
            return true;
        }

        // Contributors can view only their own evidence
        if ($user->hasPermissionTo('view-own-evidence')) {
            return $evidence->user_id === $user->id;
        }

        // Viewers can view evidence
        return $user->hasPermissionTo('view-evidence');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Organization Owner and Contributor can upload evidence
        return $user->hasPermissionTo('upload-evidence');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Evidence $evidence): bool
    {
        // Organization Owner can update any evidence
        if ($user->hasPermissionTo('update-evidence') && $user->hasRole('Organization Owner')) {
            return true;
        }

        // Contributors can update only their own evidence
        return $user->hasPermissionTo('update-evidence') && $evidence->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Evidence $evidence): bool
    {
        // Organization Owner can delete any evidence
        if ($user->hasPermissionTo('delete-evidence') && $user->hasRole('Organization Owner')) {
            return true;
        }

        // Contributors can delete only their own evidence
        return $user->hasPermissionTo('delete-evidence') && $evidence->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Evidence $evidence): bool
    {
        // Only Organization Owner can restore
        return $user->hasRole('Organization Owner');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Evidence $evidence): bool
    {
        // Only Organization Owner can permanently delete
        return $user->hasRole('Organization Owner');
    }

    /**
     * Determine whether the user can download the evidence file.
     * Only Organization Owner or Audit Manager can download.
     *
     * @param User $user
     * @param Evidence $evidence
     * @return bool
     */
    public function download(User $user, Evidence $evidence): bool
    {
        return $user->hasAnyRole(['Organization Owner', 'Audit Manager']);
    }
}
