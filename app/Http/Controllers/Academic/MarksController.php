<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamSubject;
use App\Models\Mark;
use App\Models\SchoolClass;
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
        Gate::authorize('enter', Mark::class);

        $schoolId = Tenant::getSchoolId();

        $exams = Exam::where('school_id', $schoolId)->whereHas('session', function($q) {
            $q->where('is_current', true);
        })->get();

        $classes = SchoolClass::where('school_id', $schoolId)->get();
        // Sections of the class filter, rendered server-side so the Section dropdown keeps its value on reload
        $sections = $request->filled('school_class_id')
            ? \App\Models\Section::where('school_class_id', $request->school_class_id)->orderBy('name')->get()
            : collect();
        $examSubjects = collect();
        $roster = collect();
        $existingMarks = collect();
        $selectedSubject = null;
        $showSection = false;

        // Subjects are listed per EXAM. Class and Section are optional filters:
        //  - Section chosen → subjects for that section plus any class-wide subject.
        //  - Section "All"  → every subject in the exam (optionally narrowed to the class).
        $exam = $request->filled('exam_id')
            ? Exam::where('school_id', $schoolId)->find($request->exam_id)
            : null;

        if ($exam) {
            $examSubjects = $exam->subjects()
                ->with(['subject', 'section', 'schoolClass'])
                ->when($request->filled('school_class_id'), fn ($q) => $q->where('school_class_id', $request->school_class_id))
                ->when($request->filled('section_id'), function ($q) use ($request) {
                    $q->where(fn ($w) => $w->where('section_id', $request->section_id)->orWhereNull('section_id'));
                })
                ->get()
                ->sortBy(fn ($es) => [$es->schoolClass?->name ?? '', $es->section?->name ?? '', $es->subject?->name ?? ''])
                ->values();
        }

        if ($request->filled('exam_subject_id') && $exam) {
            $selectedSubject = ExamSubject::with(['subject', 'section', 'schoolClass'])
                ->where('exam_id', $request->exam_id)
                ->findOrFail($request->exam_subject_id);

            $sessionId = AcademicSession::where('is_current', true)->where('school_id', $schoolId)->value('id');

            // The roster's section is the subject's own section; a class-wide subject can be
            // narrowed by the Section filter. With no effective section, show every section.
            $rosterSection = $selectedSubject->section_id
                ?: ($request->filled('section_id') ? (int) $request->section_id : null);
            $showSection = ! $rosterSection;

            $roster = Enrollment::where('academic_session_id', $sessionId)
                ->where('school_class_id', $selectedSubject->school_class_id)
                ->where('status', 'active')
                ->when($rosterSection, fn ($q) => $q->where('section_id', $rosterSection))
                ->with(['student', 'section'])
                ->get()
                ->filter(fn ($e) => $e->student)
                ->sortBy([
                    fn ($a, $b) => strnatcasecmp($a->section->name ?? '~', $b->section->name ?? '~'),
                    fn ($a, $b) => strnatcmp((string) $a->roll_number, (string) $b->roll_number),
                    fn ($a, $b) => strcasecmp($a->student->full_name, $b->student->full_name),
                ])
                ->values();

            $existingMarks = Mark::where('exam_subject_id', $selectedSubject->id)->get()->keyBy('student_id');
        }

        return view('academic.examinations.marks.index', compact('exams', 'classes', 'sections', 'examSubjects', 'roster', 'existingMarks', 'selectedSubject', 'showSection'));
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
