<?php

namespace App\Policies;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StaffPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('staff.manage');
    }

    public function view(User $user, Staff $staff): bool
    {
        return $user->schools()->where('school_id', $staff->school_id)->exists()
            && $user->hasPermissionTo('staff.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('staff.manage');
    }

    public function update(User $user, Staff $staff): bool
    {
        return $user->schools()->where('school_id', $staff->school_id)->exists()
            && $user->hasPermissionTo('staff.manage');
    }

    public function delete(User $user, Staff $staff): bool
    {
        return $user->schools()->where('school_id', $staff->school_id)->exists()
            && $user->hasPermissionTo('staff.manage');
    }

    public function restore(User $user, Staff $staff): bool
    {
        return $user->schools()->where('school_id', $staff->school_id)->exists()
            && $user->hasPermissionTo('staff.manage');
    }

    public function forceDelete(User $user, Staff $staff): bool
    {
        return $user->schools()->where('school_id', $staff->school_id)->exists()
            && $user->hasPermissionTo('staff.manage');
    }
}
