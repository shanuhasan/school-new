<?php

namespace App\Policies;

use App\Models\Mark;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MarkPolicy
{
    public function enter(User $user): bool
    {
        return $user->hasPermissionTo('marks.enter') || $user->hasPermissionTo('exams.update');
    }

    public function view(User $user, Mark $mark): bool
    {
        if ($user->hasRole('Student')) {
            return $user->id === $mark->student_id;
        }
        if ($user->hasRole('Parent')) {
            return $user->children()->where('users.id', $mark->student_id)->exists();
        }
        return $user->hasPermissionTo('exams.view') && $user->current_school_id === $mark->school_id;
    }
}
