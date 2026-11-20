<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use App\Events\HomeworkSubmitted;
use App\Events\HomeworkEvaluated;

class HomeworkSubmissionController extends Controller
{
    /**
     * Store a newly created resource in storage (Student submitting homework).
     */
    public function store(Request $request, Homework $homework)
    {
        Gate::authorize('create', HomeworkSubmission::class);
        
        $validated = $request->validate([
            'submission_text' => 'nullable|string',
            'attachment' => 'nullable|file|max:10240', // 10MB max
        ]);

        if (empty($validated['submission_text']) && !$request->hasFile('attachment')) {
            return back()->withErrors(['submission' => 'Please provide text or an attachment.']);
        }

        // homework_submissions.student_id references the students table, so resolve the
        // signed-in user's student record (not the user id).
        $student = $request->user()->studentProfile;
        if (! $student || $student->school_id !== $homework->school_id) {
            abort(403, 'Your account is not linked to a student record in this school.');
        }

        // A student may only submit homework set for their own class (and section, when the homework has one)
        $enrolled = $student->enrollments()
            ->where('academic_session_id', $homework->academic_session_id)
            ->where('school_class_id', $homework->school_class_id)
            ->when($homework->section_id, fn ($q) => $q->where(fn ($q) => $q->where('section_id', $homework->section_id)->orWhereNull('section_id')))
            ->where('status', 'active')
            ->exists();
        abort_unless($enrolled, 403, 'This homework is not set for your class.');

        $submission = HomeworkSubmission::firstOrNew([
            'homework_id' => $homework->id,
            'student_id' => $student->id,
        ]);
        
        // Prevent editing if evaluated
        if ($submission->exists && $submission->status === 'evaluated') {
            return back()->with('error', 'Cannot update an evaluated submission.');
        }

        $submission->school_id = \App\Facades\Tenant::getSchoolId() ?? auth()->user()->current_school_id;
        $submission->submission_text = $validated['submission_text'] ?? null;
        $submission->status = 'submitted';
        $submission->submitted_at = now();

        if ($request->hasFile('attachment')) {
            // Delete old file if exists
            if ($submission->attachment_path) {
                Storage::disk('local')->delete($submission->attachment_path);
            }
            $path = $request->file('attachment')->store('homework_submissions', 'local');
            $submission->attachment_path = $path;
        }

        $submission->save();

        event(new HomeworkSubmitted($submission));

        return back()->with('success', 'Homework submitted successfully.');
    }

    /**
     * Evaluate the submission (Teacher grading).
     */
    public function evaluate(Request $request, HomeworkSubmission $submission)
    {
        Gate::authorize('evaluate', $submission);
        
        $validated = $request->validate([
            'marks' => 'nullable|numeric|min:0|max:100',
            'teacher_feedback' => 'nullable|string',
        ]);

        $submission->update([
            'marks' => $validated['marks'] ?? null,
            'teacher_feedback' => $validated['teacher_feedback'] ?? null,
            'status' => 'evaluated',
        ]);

        event(new HomeworkEvaluated($submission));

        return back()->with('success', 'Submission evaluated successfully.');
    }
    
    /**
     * Download student's attachment
     */
    public function downloadAttachment(HomeworkSubmission $submission)
    {
        Gate::authorize('view', $submission);
        
        if (!$submission->attachment_path || !Storage::disk('local')->exists($submission->attachment_path)) {
            abort(404, 'Attachment not found.');
        }
        
        return Storage::disk('local')->download(
            $submission->attachment_path, 
            \Illuminate\Support\Str::slug($submission->student?->full_name ?: 'student') . '_submission.' . pathinfo($submission->attachment_path, PATHINFO_EXTENSION)
        );
    }
}
