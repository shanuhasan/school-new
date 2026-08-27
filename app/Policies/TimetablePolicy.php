<?php

namespace App\Policies;

use App\Models\Timetable;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TimetablePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('timetable.view') || $user->can('timetable.manage');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Timetable $timetable): bool
    {
        if (!$user->schools()->where('school_id', $timetable->school_id)->exists()) {
            return false;
        }

        return $user->can('timetable.view') || $user->can('timetable.manage');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('timetable.manage');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Timetable $timetable): bool
    {
        if (!$user->schools()->where('school_id', $timetable->school_id)->exists()) {
            return false;
        }
        return $user->can('timetable.manage');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Timetable $timetable): bool
    {
        if (!$user->schools()->where('school_id', $timetable->school_id)->exists()) {
            return false;
        }
        return $user->can('timetable.manage');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Timetable $timetable): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Timetable $timetable): bool
    {
        return false;
    }
}
