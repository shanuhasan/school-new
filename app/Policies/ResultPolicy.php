<?php

namespace App\Policies;

use App\Models\Result;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ResultPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('exams.view');
    }

    public function view(User $user, Result $result): bool
    {
        if ($user->hasRole('Student')) {
            return $user->id === $result->student_id && $result->is_published;
        }
        if ($user->hasRole('Parent')) {
            return $user->children()->where('users.id', $result->student_id)->exists() && $result->is_published;
        }
        return $user->hasPermissionTo('exams.view') && $user->current_school_id === $result->school_id;
    }

    public function publish(User $user): bool
    {
        return $user->hasPermissionTo('results.publish');
    }

    /**
     * Process (calculate) results. Staff who can publish results or edit exams may do this.
     * Super Admin is allowed via the Gate::before bypass in AppServiceProvider.
     */
    public function process(User $user): bool
    {
        return $user->hasPermissionTo('results.publish') || $user->hasPermissionTo('exams.update');
    }
}
