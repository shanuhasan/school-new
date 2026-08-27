<?php

namespace App\Policies;

use App\Models\ExamType;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ExamTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('exams.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('exams.create');
    }

    public function update(User $user, ExamType $examType): bool
    {
        return $user->hasPermissionTo('exams.update') && $user->current_school_id === $examType->school_id;
    }

    public function delete(User $user, ExamType $examType): bool
    {
        return $user->hasPermissionTo('exams.update') && $user->current_school_id === $examType->school_id;
    }
}
