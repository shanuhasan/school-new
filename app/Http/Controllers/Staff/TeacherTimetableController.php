<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Timetable;
use App\Models\AcademicSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class TeacherTimetableController extends Controller
{
    /**
     * Display the authenticated teacher's timetable.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Timetable::class);
        
        $user = Auth::user();
        if (!$user->hasRole('Teacher')) {
            return redirect()->back()->with('error', 'Only teachers can access this page.');
        }

        $currentSession = AcademicSession::forCurrentSchool()->where('is_current', true)->first();
        if (!$currentSession) {
            return redirect()->back()->with('error', 'No active academic session found.');
        }

        $timetables = Timetable::with(['schoolClass', 'section', 'subject'])
            ->where('teacher_id', $user->id)
            ->where('academic_session_id', $currentSession->id)
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        $days = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        return view('staff.timetables.teacher', compact('timetables', 'days'));
    }
}
