<?php

namespace App\Policies;

use App\Models\Control;
use App\Models\User;

class ControlPolicy
{
    /**
     * Determine whether the user can view any controls.
     */
    public function viewAny(User $user): bool
    {
        // Anyone who can view audits can view the control catalog (read-only).
        return $user->hasPermissionTo('view-audit');
    }

    /**
     * Determine whether the user can view the control.
     */
    public function view(User $user, Control $control): bool
    {
        return $user->hasPermissionTo('view-audit');
    }

    /**
     * Determine whether the user can create controls.
     */
    public function create(User $user): bool
    {
        // Controls are part of the compliance catalog: restrict to owners and audit managers.
        return $user->hasAnyRole(['Organization Owner', 'Audit Manager']);
    }

    /**
     * Determine whether the user can update the control.
     */
    public function update(User $user, Control $control): bool
    {
        return $user->hasAnyRole(['Organization Owner', 'Audit Manager']);
    }

    /**
     * Determine whether the user can delete the control.
     */
    public function delete(User $user, Control $control): bool
    {
        // Restrict deletions to Organization Owner.
        return $user->hasRole('Organization Owner');
    }

    /**
     * Determine whether the user can attach controls to a parent record (e.g., audits).
     * Filament relation managers use this ability name for belongsToMany "attach".
     */
    public function attach(User $user, mixed $model = null): bool
    {
        return $user->hasAnyRole(['Organization Owner', 'Audit Manager']);
    }

    /**
     * Determine whether the user can detach a control from a parent record (e.g., audits).
     */
    public function detach(User $user, Control $control): bool
    {
        return $user->hasAnyRole(['Organization Owner', 'Audit Manager']);
    }

    /**
     * Determine whether the user can detach any controls from a parent record (bulk detach).
     */
    public function detachAny(User $user, mixed $model = null): bool
    {
        return $user->hasAnyRole(['Organization Owner', 'Audit Manager']);
    }
}

