<?php

namespace App\Policies;

use App\Models\Designation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DesignationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('staff.manage');
    }

    public function view(User $user, Designation $designation): bool
    {
        return $user->schools()->where('school_id', $designation->school_id)->exists()
            && $user->hasPermissionTo('staff.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('staff.manage');
    }

    public function update(User $user, Designation $designation): bool
    {
        return $user->schools()->where('school_id', $designation->school_id)->exists()
            && $user->hasPermissionTo('staff.manage');
    }

    public function delete(User $user, Designation $designation): bool
    {
        return $user->schools()->where('school_id', $designation->school_id)->exists()
            && $user->hasPermissionTo('staff.manage');
    }

    public function restore(User $user, Designation $designation): bool
    {
        return $user->schools()->where('school_id', $designation->school_id)->exists()
            && $user->hasPermissionTo('staff.manage');
    }

    public function forceDelete(User $user, Designation $designation): bool
    {
        return $user->schools()->where('school_id', $designation->school_id)->exists()
            && $user->hasPermissionTo('staff.manage');
    }
}
