<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Facades\Tenant;

class EnsureTenantContext
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->current_school_id) {
            // Check if user actually belongs to this school
            // Super Admin gets a free pass to view any school they set as current
            if ($user->hasRole('Super Admin') || $user->schools()->where('school_id', $user->current_school_id)->exists()) {
                $school = $user->currentSchool;
                if ($school && $school->is_active) {
                    Tenant::setSchool($school);
                } else {
                    abort(403, 'Your school is inactive or not found.');
                }
            } else {
                abort(403, 'You do not have access to this school.');
            }
        } elseif ($user && !$user->hasRole('Super Admin')) {
             // If a regular user has no current school, but they have schools associated, 
             // maybe auto-set the first one, or just abort. For safety, we abort if it's required.
             // We can optionally auto-assign their first school to their current_school_id.
             $firstSchool = $user->schools()->first();
             if ($firstSchool && $firstSchool->is_active) {
                 $user->update(['current_school_id' => $firstSchool->id]);
                 Tenant::setSchool($firstSchool);
             } else {
                 abort(403, 'No active school context found.');
             }
        }

        return $next($request);
    }
}
