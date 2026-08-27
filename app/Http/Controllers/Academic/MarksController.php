<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSubject;
use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Facades\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MarksController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $schoolId = Tenant::getSchoolId();
        
        $exams = Exam::where('school_id', $schoolId)->whereHas('session', function($q) {
            $q->where('is_current', true);
        })->get();

        $classes = SchoolClass::where('school_id', $schoolId)->get();
        $examSubjects = [];
        $students = [];
        $existingMarks = [];

        if ($request->filled(['exam_id', 'school_class_id'])) {
            $examSubjects = ExamSubject::with('subject')
                ->where('exam_id', $request->exam_id)
                ->where('school_class_id', $request->school_class_id)
                ->where(function($q) use ($request) {
                    $q->where('section_id', $request->section_id ?? null)->orWhereNull('section_id');
                })
                ->get();
        }

        if ($request->filled('exam_subject_id')) {
            $examSubject = ExamSubject::findOrFail($request->exam_subject_id);
            
            // Get all students enrolled in this class and section
            $sessionId = \App\Models\AcademicSession::where('is_current', true)->where('school_id', $schoolId)->value('id');
            
            $students = User::role('Student')
                ->whereHas('studentProfile.enrollments', function($q) use ($sessionId, $request) {
                    $q->where('academic_session_id', $sessionId)
                      ->where('school_class_id', $request->school_class_id);
                    if ($request->filled('section_id')) {
                        $q->where('section_id', $request->section_id);
                    }
                })
                ->with('studentProfile')
                ->orderBy('name')
                ->get();

            $existingMarks = Mark::where('exam_subject_id', $examSubject->id)
                ->get()
                ->keyBy('student_id');
        }

        return view('academic.examinations.marks.index', compact('exams', 'classes', 'examSubjects', 'students', 'existingMarks'));
    }

    public function store(Request $request)
    {
        Gate::authorize('enter', Mark::class);

        $validated = $request->validate([
            'exam_subject_id' => 'required|exists:exam_subjects,id',
            'marks' => 'required|array',
            'marks.*.obtained' => 'nullable|numeric|min:0',
            'marks.*.is_absent' => 'nullable|boolean',
            'marks.*.remarks' => 'nullable|string|max:255',
        ]);

        $examSubject = ExamSubject::findOrFail($validated['exam_subject_id']);
        
        // Prevent if result is already published
        $isPublished = \App\Models\Result::where('exam_id', $examSubject->exam_id)
            ->where('school_class_id', $examSubject->school_class_id)
            ->where('is_published', true)
            ->exists();
            
        if ($isPublished) {
            return back()->with('error', 'Cannot update marks. Results for this exam have already been published.');
        }

        DB::transaction(function () use ($validated, $examSubject) {
            foreach ($validated['marks'] as $studentId => $data) {
                // Ensure marks <= max_marks
                $marksObtained = $data['obtained'] ?? null;
                if ($marksObtained !== null && $marksObtained > $examSubject->max_marks) {
                    continue; // Or throw error, but we'll skip for robustness
                }

                Mark::updateOrCreate(
                    [
                        'school_id' => Tenant::getSchoolId(),
                        'exam_subject_id' => $examSubject->id,
                        'student_id' => $studentId,
                    ],
                    [
                        'marks_obtained' => $marksObtained,
                        'is_absent' => $data['is_absent'] ?? false,
                        'remarks' => $data['remarks'] ?? null,
                    ]
                );
            }
        });

        return back()->with('success', 'Marks saved successfully.');
    }
}
