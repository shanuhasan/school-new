<?php

namespace App\Policies;

use App\Models\Grade;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class GradePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('exams.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('exams.create');
    }

    public function update(User $user, Grade $grade): bool
    {
        return $user->hasPermissionTo('exams.update') && $user->current_school_id === $grade->school_id;
    }

    public function delete(User $user, Grade $grade): bool
    {
        return $user->hasPermissionTo('exams.update') && $user->current_school_id === $grade->school_id;
    }
}
