<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Exam;
use App\Models\Homework;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        // Mirror EnsureTenantContext: a Super Admin without a selected school lands on the first one.
        if (! $user->current_school_id && $user->hasRole('Super Admin') && ($first = School::first())) {
            $user->update(['current_school_id' => $first->id]);
            $user->refresh();
        }

        $schoolId = $user->current_school_id;

        // Choose the panel by what the user can do (most capable first). Super Admin passes every
        // can() check via the Gate::before bypass, so admins land on the admin panel.
        $panel = match (true) {
            $user->can('academic.manage') => 'admin',
            $user->hasRole('Teacher') && $user->teacher => 'teacher',
            $user->hasRole('Student') && $user->studentProfile => 'student',
            $user->hasRole('Parent') && $user->guardianProfile => 'parent',
            default => 'generic',
        };

        $data = ['panel' => $panel, 'school' => $user->currentSchool];

        if ($schoolId) {
            $data += match ($panel) {
                'admin' => $this->adminData($schoolId),
                'teacher' => $this->teacherData($user, $schoolId),
                'student' => $this->studentData($user, $schoolId),
                'parent' => $this->parentData($user, $schoolId),
                default => [],
            };
        }

        return view('dashboard', $data);
    }

    /** Queries on this route run without the tenant middleware, so scope by school_id explicitly. */
    private function forSchool(Builder $query, int $schoolId): Builder
    {
        return $query->withoutGlobalScope('school')->where('school_id', $schoolId);
    }

    /** Turn a status=>count map into present/late/half_day/absent + attendance rate. */
    private function summariseAttendance($counts): array
    {
        $marked = (int) collect($counts)->sum();
        $good = (int) (($counts['present'] ?? 0) + ($counts['late'] ?? 0) + ($counts['half_day'] ?? 0));

        return [
            'marked' => $marked,
            'present' => (int) ($counts['present'] ?? 0),
            'late' => (int) ($counts['late'] ?? 0),
            'half_day' => (int) ($counts['half_day'] ?? 0),
            'absent' => (int) ($counts['absent'] ?? 0),
            'rate' => $marked > 0 ? (int) round($good / $marked * 100) : null,
        ];
    }

    private function upcomingExams(int $schoolId, int $limit = 4)
    {
        return $this->forSchool(Exam::query(), $schoolId)
            ->with('examType')
            ->whereDate('end_date', '>=', now()->toDateString())
            ->orderBy('start_date')
            ->limit($limit)
            ->get();
    }

    private function adminData(int $schoolId): array
    {
        $today = now()->toDateString();

        $counts = $this->forSchool(Attendance::query(), $schoolId)
            ->whereDate('date', $today)
            ->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return [
            'stats' => [
                'students' => $this->forSchool(Student::query(), $schoolId)->where('status', 'active')->count(),
                'teachers' => $this->forSchool(Teacher::query(), $schoolId)->where('status', 'active')->count(),
                'staff' => $this->forSchool(Staff::query(), $schoolId)->where('status', 'active')->count(),
                'classes' => $this->forSchool(SchoolClass::query(), $schoolId)->count(),
            ],
            'attendance' => $this->summariseAttendance($counts),
            'upcomingExams' => $this->upcomingExams($schoolId),
            'homeworkDue' => $this->forSchool(Homework::query(), $schoolId)
                ->with(['subject', 'schoolClass', 'section'])
                ->whereDate('due_date', '>=', $today)
                ->orderBy('due_date')->limit(5)->get(),
        ];
    }

    private function teacherData($user, int $schoolId): array
    {
        $teacher = $user->teacher->load(['classes:id,name', 'subjects:id,name']);
        $today = now()->toDateString();

        return [
            'teacher' => $teacher,
            'myHomework' => $this->forSchool(Homework::query(), $schoolId)
                ->where('teacher_id', $user->id)
                ->with(['subject', 'schoolClass', 'section'])
                ->whereDate('due_date', '>=', $today)
                ->orderBy('due_date')->limit(6)->get(),
            'homeworkSetCount' => $this->forSchool(Homework::query(), $schoolId)->where('teacher_id', $user->id)->count(),
            'upcomingExams' => $this->upcomingExams($schoolId),
        ];
    }

    private function studentData($user, int $schoolId): array
    {
        $student = $user->studentProfile;

        $enrollment = $student->enrollments()
            ->where('status', 'active')
            ->whereHas('academicSession', fn ($q) => $q->where('is_current', true))
            ->with(['schoolClass', 'section', 'academicSession'])
            ->latest()->first()
            ?? $student->enrollments()->with(['schoolClass', 'section', 'academicSession'])->latest()->first();

        $attendance = null;
        $homeworkDue = collect();

        if ($enrollment) {
            $counts = $this->forSchool(Attendance::query(), $schoolId)
                ->where('student_id', $student->id)
                ->where('academic_session_id', $enrollment->academic_session_id)
                ->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
            $attendance = $this->summariseAttendance($counts);

            $homeworkDue = $this->forSchool(Homework::query(), $schoolId)
                ->where('school_class_id', $enrollment->school_class_id)
                ->where('academic_session_id', $enrollment->academic_session_id)
                ->when($enrollment->section_id, fn ($q) => $q->where(fn ($w) => $w->whereNull('section_id')->orWhere('section_id', $enrollment->section_id)))
                ->with('subject')
                ->whereDate('due_date', '>=', now()->toDateString())
                ->orderBy('due_date')->limit(6)->get();
        }

        return [
            'student' => $student,
            'enrollment' => $enrollment,
            'attendance' => $attendance,
            'homeworkDue' => $homeworkDue,
            'upcomingExams' => $this->upcomingExams($schoolId),
        ];
    }

    private function parentData($user, int $schoolId): array
    {
        $children = $user->guardianProfile->students()
            ->with(['enrollments' => fn ($q) => $q->where('status', 'active')->with(['schoolClass', 'section', 'academicSession'])])
            ->get()
            ->map(function (Student $child) use ($schoolId) {
                $enrollment = $child->enrollments->firstWhere(fn ($e) => $e->academicSession?->is_current)
                    ?? $child->enrollments->first();

                $homeworkDue = 0;
                $rate = null;
                if ($enrollment) {
                    $counts = $this->forSchool(Attendance::query(), $schoolId)
                        ->where('student_id', $child->id)
                        ->where('academic_session_id', $enrollment->academic_session_id)
                        ->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
                    $rate = $this->summariseAttendance($counts)['rate'];

                    $homeworkDue = $this->forSchool(Homework::query(), $schoolId)
                        ->where('school_class_id', $enrollment->school_class_id)
                        ->where('academic_session_id', $enrollment->academic_session_id)
                        ->whereDate('due_date', '>=', now()->toDateString())->count();
                }

                return (object) ['student' => $child, 'enrollment' => $enrollment, 'attendanceRate' => $rate, 'homeworkDue' => $homeworkDue];
            });

        return ['children' => $children];
    }
}
