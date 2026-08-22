<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\School;

class SchoolSwitcherController extends Controller
{
    public function switch(Request $request)
    {
        $request->validate([
            'school_id' => ['required', 'exists:schools,id'],
        ]);

        $user = $request->user();
        $schoolId = $request->input('school_id');

        // Check if user is Super Admin or actually belongs to the school
        if ($user->hasRole('Super Admin') || $user->schools()->where('school_id', $schoolId)->exists()) {
            
            $school = School::find($schoolId);
            
            if ($school && $school->is_active) {
                $user->update(['current_school_id' => $schoolId]);
                return back()->with('success', 'Switched to ' . $school->name);
            }

            return back()->with('error', 'Selected school is inactive.');
        }

        abort(403, 'You do not have access to this school.');
    }
}
