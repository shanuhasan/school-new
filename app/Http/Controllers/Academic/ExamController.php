<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamType;
use App\Models\ExamSubject;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\AcademicSession;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Facades\Tenant;

class ExamController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Exam::class);
        $schoolId = Tenant::getSchoolId();
        $sessionId = AcademicSession::where('is_current', true)->where('school_id', $schoolId)->value('id');
        
        $exams = Exam::with('examType')->where('school_id', $schoolId)->where('academic_session_id', $sessionId)->get();
        $examTypes = ExamType::where('school_id', $schoolId)->get();

        return view('academic.examinations.exams.index', compact('exams', 'examTypes'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Exam::class);
        $schoolId = Tenant::getSchoolId();
        $sessionId = AcademicSession::where('is_current', true)->where('school_id', $schoolId)->value('id');

        if (!$sessionId) {
            return back()->with('error', 'No active academic session found.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'exam_type_id' => 'required|exists:exam_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'description' => 'nullable|string'
        ]);

        $validated['school_id'] = $schoolId;
        $validated['academic_session_id'] = $sessionId;

        Exam::create($validated);
        return back()->with('success', 'Exam created successfully.');
    }

    public function show(Exam $exam)
    {
        $this->authorize('view', $exam);
        $schoolId = Tenant::getSchoolId();
        
        $exam->load(['subjects.schoolClass', 'subjects.subject', 'subjects.section']);
        $classes = SchoolClass::where('school_id', $schoolId)->get();
        $subjects = Subject::where('school_id', $schoolId)->get();

        return view('academic.examinations.exams.show', compact('exam', 'classes', 'subjects'));
    }

    public function assignSubject(Request $request, Exam $exam)
    {
        $this->authorize('update', $exam);
        
        $validated = $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'exam_date' => 'required|date',
            'max_marks' => 'required|numeric|min:1',
            'passing_marks' => 'required|numeric|min:0|lte:max_marks',
        ]);

        // Check for duplicates
        $exists = ExamSubject::where('exam_id', $exam->id)
            ->where('school_class_id', $validated['school_class_id'])
            ->where('section_id', $validated['section_id'] ?? null)
            ->where('subject_id', $validated['subject_id'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'Subject already assigned for this class/section.');
        }

        $exam->subjects()->create($validated);
        return back()->with('success', 'Subject assigned successfully.');
    }

    public function destroySubject(ExamSubject $examSubject)
    {
        $this->authorize('update', $examSubject->exam);
        $examSubject->delete();
        return back()->with('success', 'Assigned subject removed.');
    }
}
