<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DepartmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('staff.manage');
    }

    public function view(User $user, Department $department): bool
    {
        return $user->schools()->where('school_id', $department->school_id)->exists()
            && $user->hasPermissionTo('staff.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('staff.manage');
    }

    public function update(User $user, Department $department): bool
    {
        return $user->schools()->where('school_id', $department->school_id)->exists()
            && $user->hasPermissionTo('staff.manage');
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->schools()->where('school_id', $department->school_id)->exists()
            && $user->hasPermissionTo('staff.manage');
    }

    public function restore(User $user, Department $department): bool
    {
        return $user->schools()->where('school_id', $department->school_id)->exists()
            && $user->hasPermissionTo('staff.manage');
    }

    public function forceDelete(User $user, Department $department): bool
    {
        return $user->schools()->where('school_id', $department->school_id)->exists()
            && $user->hasPermissionTo('staff.manage');
    }
}
