<?php

namespace App\Policies;

use App\Models\AcademicSession;
use App\Models\User;

class AcademicSessionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('exams.view') || $user->hasPermissionTo('attendance.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AcademicSession $academicSession): bool
    {
        return $user->schools()->where('school_id', $academicSession->school_id)->exists()
            && ($user->hasPermissionTo('exams.view') || $user->hasPermissionTo('attendance.view'));
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('exams.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AcademicSession $academicSession): bool
    {
        return $user->schools()->where('school_id', $academicSession->school_id)->exists()
            && $user->hasPermissionTo('exams.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AcademicSession $academicSession): bool
    {
        return $user->schools()->where('school_id', $academicSession->school_id)->exists()
            && $user->hasPermissionTo('exams.delete');
    }
}
