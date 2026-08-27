<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Grade;
use App\Models\Result;
use App\Models\Mark;
use App\Models\ExamSubject;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Facades\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ResultController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $schoolId = Tenant::getSchoolId();
        
        $exams = Exam::where('school_id', $schoolId)->whereHas('session', function($q) {
            $q->where('is_current', true);
        })->get();

        $classes = SchoolClass::where('school_id', $schoolId)->get();
        $results = [];

        if ($request->filled(['exam_id', 'school_class_id', 'section_id'])) {
            $results = Result::with(['student', 'grade'])
                ->where('exam_id', $request->exam_id)
                ->where('school_class_id', $request->school_class_id)
                ->where('section_id', $request->section_id)
                ->get();
        }

        return view('academic.examinations.results.index', compact('exams', 'classes', 'results'));
    }

    public function process(Request $request)
    {
        Gate::authorize('create', Result::class); // using viewAny or create equivalent, let's just check exams.update
        
        if (!auth()->user()->hasPermissionTo('exams.update')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'school_class_id' => 'required|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
        ]);

        $schoolId = Tenant::getSchoolId();
        $sessionId = \App\Models\AcademicSession::where('is_current', true)->where('school_id', $schoolId)->value('id');

        $examSubjects = ExamSubject::where('exam_id', $validated['exam_id'])
            ->where('school_class_id', $validated['school_class_id'])
            ->where(function($q) use ($validated) {
                $q->where('section_id', $validated['section_id'] ?? null)->orWhereNull('section_id');
            })
            ->get();

        if ($examSubjects->isEmpty()) {
            return back()->with('error', 'No subjects scheduled for this exam and class.');
        }

        // Get students in class/section
        $students = Student::whereHas('enrollments', function($q) use ($sessionId, $validated) {
                $q->where('academic_session_id', $sessionId)
                  ->where('school_class_id', $validated['school_class_id']);
                if (isset($validated['section_id'])) {
                    $q->where('section_id', $validated['section_id']);
                }
            })->get();

        $grades = Grade::where('school_id', $schoolId)->orderBy('min_percentage', 'desc')->get();

        DB::transaction(function () use ($students, $examSubjects, $grades, $validated, $schoolId, $sessionId) {
            foreach ($students as $student) {
                $totalMaxMarks = 0;
                $totalObtainedMarks = 0;
                $hasFailedSubject = false;
                $allSubjectsMarked = true;

                foreach ($examSubjects as $subject) {
                    $mark = Mark::where('exam_subject_id', $subject->id)
                        ->where('student_id', $student->id)
                        ->first();

                    $totalMaxMarks += $subject->max_marks;

                    if ($mark && !$mark->is_absent) {
                        $totalObtainedMarks += $mark->marks_obtained;
                        if ($mark->marks_obtained < $subject->passing_marks) {
                            $hasFailedSubject = true;
                        }
                    } else {
                        // absent or not marked
                        $hasFailedSubject = true; // Treating absent as failed for the subject
                        if (!$mark) {
                            $allSubjectsMarked = false; // missing marks entry entirely
                        }
                    }
                }

                if (!$allSubjectsMarked) {
                    continue; // Skip processing if some marks are not entered yet
                }

                $percentage = ($totalMaxMarks > 0) ? ($totalObtainedMarks / $totalMaxMarks) * 100 : 0;
                
                // Determine Grade
                $assignedGradeId = null;
                foreach ($grades as $grade) {
                    if ($percentage >= $grade->min_percentage && $percentage <= $grade->max_percentage) {
                        $assignedGradeId = $grade->id;
                        break;
                    }
                }

                $status = $hasFailedSubject ? 'fail' : 'pass';

                Result::updateOrCreate(
                    [
                        'school_id' => $schoolId,
                        'academic_session_id' => $sessionId,
                        'exam_id' => $validated['exam_id'],
                        'school_class_id' => $validated['school_class_id'],
                        'section_id' => $validated['section_id'],
                        'student_id' => $student->id,
                    ],
                    [
                        'total_max_marks' => $totalMaxMarks,
                        'total_obtained_marks' => $totalObtainedMarks,
                        'percentage' => $percentage,
                        'grade_id' => $assignedGradeId,
                        'status' => $status,
                    ]
                );
            }
        });

        return back()->with('success', 'Results processed successfully for all students.');
    }

    public function publish(Request $request)
    {
        Gate::authorize('publish', Result::class);

        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'school_class_id' => 'required|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
        ]);

        $query = Result::where('exam_id', $validated['exam_id'])
            ->where('school_class_id', $validated['school_class_id']);
            
        if (isset($validated['section_id'])) {
            $query->where('section_id', $validated['section_id']);
        } else {
            $query->whereNull('section_id');
        }
            
        $query->update([
                'is_published' => true,
                'published_at' => now()
            ]);

        return back()->with('success', 'Results published successfully. Marks are now locked.');
    }
}
