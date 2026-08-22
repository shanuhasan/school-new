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
        } elseif ($user) {
             // If a user has no current school, auto-assign one so they have a valid context.
             // Super Admins get the first system school. Regular users get their first attached school.
             $firstSchool = $user->hasRole('Super Admin') ? \App\Models\School::first() : $user->schools()->first();
             
             if ($firstSchool) {
                 $user->update(['current_school_id' => $firstSchool->id]);
                 Tenant::setSchool($firstSchool);
             } else {
                 if (!$user->hasRole('Super Admin')) {
                     abort(403, 'No active school context found.');
                 }
             }
        }

        return $next($request);
    }
}
