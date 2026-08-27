<?php

namespace App\Policies;

use App\Models\HomeworkSubmission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class HomeworkSubmissionPolicy
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
    public function view(User $user, HomeworkSubmission $homeworkSubmission): bool
    {
        if ($user->current_school_id !== $homeworkSubmission->school_id) {
            return false;
        }

        // Student can only view their own submission
        if ($user->hasRole('Student')) {
            return $user->id === $homeworkSubmission->student_id;
        }

        // Parent can view their children's submissions
        if ($user->hasRole('Parent')) {
            return $user->children()->where('users.id', $homeworkSubmission->student_id)->exists();
        }

        // Teachers can view submissions for homework they created (or all if admin)
        if ($user->hasRole('Teacher')) {
            return $homeworkSubmission->homework->teacher_id === $user->id;
        }

        return $user->hasPermissionTo('homework.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('homework.submit');
    }

    /**
     * Determine whether the user can update the model (submit/edit submission).
     */
    public function update(User $user, HomeworkSubmission $homeworkSubmission): bool
    {
        if ($user->current_school_id !== $homeworkSubmission->school_id) {
            return false;
        }

        // Only the student can update their own submission (if it's not already evaluated)
        if ($user->hasRole('Student')) {
            return $user->id === $homeworkSubmission->student_id && 
                   $homeworkSubmission->status !== 'evaluated';
        }

        return false;
    }

    /**
     * Determine whether the user can evaluate the submission.
     */
    public function evaluate(User $user, HomeworkSubmission $homeworkSubmission): bool
    {
        if ($user->current_school_id !== $homeworkSubmission->school_id) {
            return false;
        }

        if (!$user->hasPermissionTo('homework.manage')) {
            return false;
        }

        // If teacher, they must be the one who created the homework
        if ($user->hasRole('Teacher')) {
            return $homeworkSubmission->homework->teacher_id === $user->id;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, HomeworkSubmission $homeworkSubmission): bool
    {
        return false; // Submissions shouldn't be deleted, only updated or reset
    }
}
