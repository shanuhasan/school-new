<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Exam;
use App\Models\Homework;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        // Mirror EnsureTenantContext: a Super Admin without a selected school lands on the first one.
        if (! $user->current_school_id && $user->hasRole('Super Admin') && ($first = \App\Models\School::first())) {
            $user->update(['current_school_id' => $first->id]);
            $user->refresh();
        }

        $schoolId = $user->current_school_id;
        $today = now()->toDateString();

        $data = [
            'school' => $user->currentSchool,
            'stats' => null,
            'attendance' => null,
            'upcomingExams' => collect(),
            'homeworkDue' => collect(),
        ];

        if (! $schoolId) {
            return view('dashboard', $data);
        }

        // Filter by school explicitly: this route runs without the tenant middleware,
        // so the BelongsToSchool global scope may not be active here.
        $forSchool = fn ($query) => $query->withoutGlobalScope('school')->where('school_id', $schoolId);

        $data['stats'] = [
            'students' => $forSchool(Student::query())->where('status', 'active')->count(),
            'teachers' => $forSchool(Teacher::query())->where('status', 'active')->count(),
            'staff' => $forSchool(Staff::query())->where('status', 'active')->count(),
            'classes' => $forSchool(SchoolClass::query())->count(),
        ];

        $todayCounts = $forSchool(Attendance::query())
            ->whereDate('date', $today)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $marked = (int) $todayCounts->sum();
        $data['attendance'] = [
            'marked' => $marked,
            'present' => (int) ($todayCounts['present'] ?? 0),
            'late' => (int) ($todayCounts['late'] ?? 0),
            'half_day' => (int) ($todayCounts['half_day'] ?? 0),
            'absent' => (int) ($todayCounts['absent'] ?? 0),
            'rate' => $marked > 0
                ? round((($todayCounts['present'] ?? 0) + ($todayCounts['late'] ?? 0) + ($todayCounts['half_day'] ?? 0)) / $marked * 100)
                : null,
        ];

        $data['upcomingExams'] = $forSchool(Exam::query())
            ->with('examType')
            ->whereDate('end_date', '>=', $today)
            ->orderBy('start_date')
            ->limit(4)
            ->get();

        $data['homeworkDue'] = $forSchool(Homework::query())
            ->with(['subject', 'schoolClass', 'section'])
            ->whereDate('due_date', '>=', $today)
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        return view('dashboard', $data);
    }
}
