<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use App\Models\SchoolClass;

class AttendancePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('attendance.view') || $user->can('attendance.manage');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Attendance $attendance): bool
    {
        if (!$user->schools()->where('school_id', $attendance->school_id)->exists()) {
            return false;
        }

        return $user->can('attendance.view') || $user->can('attendance.manage');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('attendance.create') || $user->can('attendance.manage');
    }

    /**
     * Determine whether the user can manage (create/update) attendance for a specific class.
     */
    public function manageForClass(User $user, SchoolClass $schoolClass): bool
    {
        if (!$user->schools()->where('school_id', $schoolClass->school_id)->exists()) {
            return false;
        }

        if ($user->can('attendance.manage')) {
            return true; // Super/School Admins
        }

        if ($user->can('attendance.create') || $user->can('attendance.update')) {
            if ($user->hasRole('Teacher')) {
                return $user->teacherProfile && $user->teacherProfile->classes()->where('school_classes.id', $schoolClass->id)->exists();
            }
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Attendance $attendance): bool
    {
        if (!$user->schools()->where('school_id', $attendance->school_id)->exists()) {
            return false;
        }

        return $this->manageForClass($user, $attendance->schoolClass);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Attendance $attendance): bool
    {
        if (!$user->schools()->where('school_id', $attendance->school_id)->exists()) {
            return false;
        }

        return $user->can('attendance.manage'); 
    }

    /**
     * Determine whether the user can view reports.
     */
    public function viewReports(User $user): bool
    {
        return $user->can('attendance.view_reports') || $user->can('attendance.manage');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Attendance $attendance): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Attendance $attendance): bool
    {
        return false;
    }
}
