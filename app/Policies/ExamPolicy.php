<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ExamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('exams.view');
    }

    public function view(User $user, Exam $exam): bool
    {
        return $user->hasPermissionTo('exams.view') && $user->current_school_id === $exam->school_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('exams.create');
    }

    public function update(User $user, Exam $exam): bool
    {
        return $user->hasPermissionTo('exams.update') && $user->current_school_id === $exam->school_id;
    }

    public function delete(User $user, Exam $exam): bool
    {
        return $user->hasPermissionTo('exams.update') && $user->current_school_id === $exam->school_id;
    }
}
