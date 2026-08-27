<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\AcademicSession;
use App\Models\Enrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource (Filter form).
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Attendance::class);
        
        $classes = SchoolClass::forCurrentSchool()->get();
        if (Auth::user()->hasRole('Teacher') && Auth::user()->teacherProfile) {
            $classes = Auth::user()->teacherProfile->classes;
        }
        
        $sections = collect();
        if ($request->filled('school_class_id')) {
            $class = SchoolClass::find($request->school_class_id);
            if ($class) {
                $sections = $class->sections;
            }
        }
        
        return view('academic.attendance.index', compact('classes', 'sections'));
    }

    /**
     * Show the form for marking attendance.
     */
    public function mark(Request $request)
    {
        Gate::authorize('create', Attendance::class);
        
        $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'section_id' => 'required|exists:sections,id',
            'date' => 'required|date|before_or_equal:today',
        ]);
        
        $schoolClass = SchoolClass::findOrFail($request->school_class_id);
        
        // Authorization check for Teacher
        if (Auth::user()->hasRole('Teacher')) {
            Gate::authorize('manageForClass', [Attendance::class, $schoolClass]);
        }
        
        $section = Section::findOrFail($request->section_id);
        $date = $request->date;
        $currentSession = AcademicSession::forCurrentSchool()->where('is_current', true)->first();
        
        if (!$currentSession) {
            return redirect()->back()->with('error', 'No active academic session found.');
        }

        // Get enrollments with students
        $enrollments = Enrollment::with('student')
            ->where('school_class_id', $schoolClass->id)
            ->where('section_id', $section->id)
            ->where('academic_session_id', $currentSession->id)
            ->where('status', 'active')
            ->get();
            
        $studentIds = $enrollments->pluck('student_id')->toArray();
        
        // Get existing attendance for this date
        $existingAttendance = Attendance::where('school_class_id', $schoolClass->id)
            ->where('section_id', $section->id)
            ->where('date', $date)
            ->where('academic_session_id', $currentSession->id)
            ->get()
            ->keyBy('student_id');

        return view('academic.attendance.mark', compact('schoolClass', 'section', 'date', 'enrollments', 'existingAttendance'));
    }

    /**
     * Store attendance in bulk.
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Attendance::class);
        
        $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'section_id' => 'required|exists:sections,id',
            'date' => 'required|date|before_or_equal:today',
            'attendance' => 'required|array',
            'attendance.*.status' => 'required|in:present,absent,late,half_day',
            'attendance.*.remarks' => 'nullable|string|max:255',
        ]);
        
        $schoolClass = SchoolClass::findOrFail($request->school_class_id);
        
        // Authorization check for Teacher
        if (Auth::user()->hasRole('Teacher')) {
            Gate::authorize('manageForClass', [Attendance::class, $schoolClass]);
        }
        
        $currentSession = AcademicSession::forCurrentSchool()->where('is_current', true)->first();
        
        if (!$currentSession) {
            return redirect()->route('academic.attendance.index')->with('error', 'No active academic session found.');
        }
        
        $schoolId = \App\Facades\Tenant::getSchoolId();
        if (!$schoolId) {
            $schoolId = Auth::user()->current_school_id;
        }
        $date = $request->date;
        
        $records = [];
        $now = now();
        
        foreach ($request->attendance as $studentId => $data) {
            $records[] = [
                'school_id' => $schoolId,
                'academic_session_id' => $currentSession->id,
                'student_id' => $studentId,
                'school_class_id' => $request->school_class_id,
                'section_id' => $request->section_id,
                'date' => $date,
                'status' => $data['status'],
                'remarks' => $data['remarks'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::transaction(function () use ($records) {
            Attendance::upsert(
                $records, 
                ['school_id', 'academic_session_id', 'student_id', 'date'], // Unique columns
                ['school_class_id', 'section_id', 'status', 'remarks', 'updated_at'] // Columns to update on duplicate
            );
        });

        return redirect()->route('academic.attendance.index')->with('success', 'Attendance marked successfully.');
    }
}
