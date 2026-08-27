<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use App\Models\Timetable;
use App\Models\AcademicSession;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class TimetableController extends Controller
{
    /**
     * Display a listing of the resource (Filter form).
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Timetable::class);
        
        $classes = SchoolClass::forCurrentSchool()->get();
        $sections = collect();
        if ($request->filled('school_class_id')) {
            $class = SchoolClass::find($request->school_class_id);
            if ($class) {
                $sections = $class->sections;
            }
        }
        
        return view('academic.timetables.index', compact('classes', 'sections'));
    }

    /**
     * Manage timetable for a specific class and section.
     */
    public function manage(Request $request)
    {
        Gate::authorize('viewAny', Timetable::class);
        
        $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'section_id' => 'required|exists:sections,id',
        ]);
        
        $schoolClass = SchoolClass::findOrFail($request->school_class_id);
        $section = Section::findOrFail($request->section_id);
        
        $currentSession = AcademicSession::forCurrentSchool()->where('is_current', true)->first();
        if (!$currentSession) {
            return redirect()->back()->with('error', 'No active academic session found.');
        }

        $timetables = Timetable::with(['subject', 'teacher'])
            ->where('school_class_id', $schoolClass->id)
            ->where('section_id', $section->id)
            ->where('academic_session_id', $currentSession->id)
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');
            
        $subjects = Subject::forCurrentSchool()->get();
        $teachers = User::role('Teacher')->whereHas('schools', function($q) {
            $q->where('school_id', \App\Facades\Tenant::getSchoolId() ?? auth()->user()->current_school_id);
        })->get();

        $days = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        return view('academic.timetables.manage', compact('schoolClass', 'section', 'timetables', 'subjects', 'teachers', 'days'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Timetable::class);
        
        $validated = $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            'day_of_week' => 'required|integer|between:1,7',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room_number' => 'nullable|string|max:50',
        ]);
        
        $currentSession = AcademicSession::forCurrentSchool()->where('is_current', true)->first();
        if (!$currentSession) {
            return redirect()->back()->with('error', 'No active academic session found.');
        }
        
        $validated['academic_session_id'] = $currentSession->id;
        $validated['school_id'] = \App\Facades\Tenant::getSchoolId() ?? auth()->user()->current_school_id;

        // Conflict Validation: Teacher overlap
        $teacherConflict = Timetable::where('teacher_id', $validated['teacher_id'])
            ->where('academic_session_id', $validated['academic_session_id'])
            ->where('day_of_week', $validated['day_of_week'])
            ->where(function($query) use ($validated) {
                $query->where(function($q) use ($validated) {
                    $q->where('start_time', '<', $validated['end_time'])
                      ->where('end_time', '>', $validated['start_time']);
                });
            })->exists();

        if ($teacherConflict) {
            throw ValidationException::withMessages([
                'teacher_id' => 'The selected teacher is already scheduled for another class during this time.'
            ]);
        }

        // Conflict Validation: Class overlap
        $classConflict = Timetable::where('school_class_id', $validated['school_class_id'])
            ->where('section_id', $validated['section_id'])
            ->where('academic_session_id', $validated['academic_session_id'])
            ->where('day_of_week', $validated['day_of_week'])
            ->where(function($query) use ($validated) {
                $query->where(function($q) use ($validated) {
                    $q->where('start_time', '<', $validated['end_time'])
                      ->where('end_time', '>', $validated['start_time']);
                });
            })->exists();

        if ($classConflict) {
            throw ValidationException::withMessages([
                'start_time' => 'This class already has a subject scheduled during this time.'
            ]);
        }

        Timetable::create($validated);

        return redirect()->route('academic.timetables.manage', [
            'school_class_id' => $validated['school_class_id'],
            'section_id' => $validated['section_id']
        ])->with('success', 'Timetable entry added successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Timetable $timetable)
    {
        Gate::authorize('delete', $timetable);
        
        $classId = $timetable->school_class_id;
        $sectionId = $timetable->section_id;
        
        $timetable->delete();

        return redirect()->route('academic.timetables.manage', [
            'school_class_id' => $classId,
            'section_id' => $sectionId
        ])->with('success', 'Timetable entry removed.');
    }
}
