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
        $this->authorize('process', Result::class);

        $schoolId = Tenant::getSchoolId();
        
        $exams = Exam::where('school_id', $schoolId)->whereHas('session', function($q) {
            $q->where('is_current', true);
        })->get();

        $classes = SchoolClass::where('school_id', $schoolId)->get();
        $results = collect();

        // Section is an optional filter; with none, show the whole class.
        if ($request->filled(['exam_id', 'school_class_id'])) {
            $results = Result::with(['student', 'grade', 'section'])
                ->where('exam_id', $request->exam_id)
                ->where('school_class_id', $request->school_class_id)
                ->when($request->filled('section_id'), fn ($q) => $q->where('section_id', $request->section_id))
                ->get()
                ->sortByDesc('percentage')
                ->values();
        }

        return view('academic.examinations.results.index', compact('exams', 'classes', 'results'));
    }

    public function process(Request $request)
    {
        // Super Admin passes via Gate::before; others need results.publish or exams.update.
        $this->authorize('process', Result::class);

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

        // Enrollments for this class/session (optionally one section). Each enrollment carries the
        // student's actual section, which is what we record on the result.
        $enrollments = \App\Models\Enrollment::where('academic_session_id', $sessionId)
            ->where('school_class_id', $validated['school_class_id'])
            ->where('status', 'active')
            ->when(! empty($validated['section_id']), fn ($q) => $q->where('section_id', $validated['section_id']))
            ->with('student')
            ->get()
            ->filter(fn ($e) => $e->student);

        $grades = Grade::where('school_id', $schoolId)->orderBy('min_percentage', 'desc')->get();

        $processed = 0;

        DB::transaction(function () use ($enrollments, $examSubjects, $grades, $validated, $schoolId, $sessionId, &$processed) {
            foreach ($enrollments as $enrollment) {
                $student = $enrollment->student;
                $totalMaxMarks = 0;
                $totalObtainedMarks = 0;
                $hasFailedSubject = false;
                $allSubjectsMarked = true;

                // Only count subjects that apply to this student's section (section-specific or class-wide)
                $studentSubjects = $examSubjects->filter(fn ($s) => $s->section_id === null || $s->section_id == $enrollment->section_id);

                foreach ($studentSubjects as $subject) {
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

                // Match on the unique key (exam + student); record the student's actual section.
                Result::updateOrCreate(
                    [
                        'exam_id' => $validated['exam_id'],
                        'student_id' => $student->id,
                    ],
                    [
                        'school_id' => $schoolId,
                        'academic_session_id' => $sessionId,
                        'school_class_id' => $validated['school_class_id'],
                        'section_id' => $enrollment->section_id,
                        'total_max_marks' => $totalMaxMarks,
                        'total_obtained_marks' => $totalObtainedMarks,
                        'percentage' => $percentage,
                        'grade_id' => $assignedGradeId,
                        'status' => $status,
                    ]
                );
                $processed++;
            }
        });

        if ($processed === 0) {
            return back()->with('error', 'No results were processed. Make sure marks are entered for every subject first.');
        }

        $skipped = $enrollments->count() - $processed;

        return back()->with('success', "Results processed for {$processed} "
            . \Illuminate\Support\Str::plural('student', $processed)
            . ($skipped > 0 ? ". {$skipped} skipped (marks not fully entered)." : '.'));
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
            ->where('school_class_id', $validated['school_class_id'])
            // Section filter narrows to one section; with no filter, publish the whole class.
            ->when(! empty($validated['section_id']), fn ($q) => $q->where('section_id', $validated['section_id']));

        $count = $query->update([
            'is_published' => true,
            'published_at' => now(),
        ]);

        if ($count === 0) {
            return back()->with('error', 'Nothing to publish. Process the results first.');
        }

        return back()->with('success', "Results published for {$count} " . \Illuminate\Support\Str::plural('student', $count) . '. Marks are now locked.');
    }
}
