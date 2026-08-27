<?php

namespace App\Policies;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TeacherPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('staff.manage');
    }

    public function view(User $user, Teacher $teacher): bool
    {
        return $user->schools()->where('school_id', $teacher->school_id)->exists()
            && $user->hasPermissionTo('staff.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('staff.manage');
    }

    public function update(User $user, Teacher $teacher): bool
    {
        return $user->schools()->where('school_id', $teacher->school_id)->exists()
            && $user->hasPermissionTo('staff.manage');
    }

    public function delete(User $user, Teacher $teacher): bool
    {
        return $user->schools()->where('school_id', $teacher->school_id)->exists()
            && $user->hasPermissionTo('staff.manage');
    }

    public function restore(User $user, Teacher $teacher): bool
    {
        return $user->schools()->where('school_id', $teacher->school_id)->exists()
            && $user->hasPermissionTo('staff.manage');
    }

    public function forceDelete(User $user, Teacher $teacher): bool
    {
        return $user->schools()->where('school_id', $teacher->school_id)->exists()
            && $user->hasPermissionTo('staff.manage');
    }
}
