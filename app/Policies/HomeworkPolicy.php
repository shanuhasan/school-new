<?php

namespace App\Policies;

use App\Models\Homework;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class HomeworkPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('homework.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Homework $homework): bool
    {
        if ($user->current_school_id !== $homework->school_id) {
            return false;
        }

        if ($user->hasRole('Student')) {
            // Student must belong to the class and section of the homework
            $studentProfile = $user->studentProfile;
            if (!$studentProfile) return false;
            
            $enrollment = $studentProfile->enrollments()->where('academic_session_id', $homework->academic_session_id)->first();
            
            return $enrollment && 
                   $enrollment->school_class_id === $homework->school_class_id &&
                   $enrollment->section_id === $homework->section_id;
        }

        if ($user->hasRole('Parent')) {
            // Parent can view if any of their children belong to the class and section
            $children = $user->children();
            foreach ($children->get() as $child) {
                $profile = $child->studentProfile;
                if ($profile) {
                    $enrollment = $profile->enrollments()->where('academic_session_id', $homework->academic_session_id)->first();
                    if ($enrollment && $enrollment->school_class_id === $homework->school_class_id && $enrollment->section_id === $homework->section_id) {
                        return true;
                    }
                }
            }
            return false;
        }

        return $user->hasPermissionTo('homework.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('homework.manage');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Homework $homework): bool
    {
        if ($user->current_school_id !== $homework->school_id) {
            return false;
        }
        
        if (!$user->hasPermissionTo('homework.manage')) {
            return false;
        }

        // If teacher, can only update their own homework
        if ($user->hasRole('Teacher') && $homework->teacher_id !== $user->id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Homework $homework): bool
    {
        return $this->update($user, $homework);
    }
}
