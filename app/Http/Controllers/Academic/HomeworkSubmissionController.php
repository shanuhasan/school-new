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

        $submission = HomeworkSubmission::firstOrNew([
            'homework_id' => $homework->id,
            'student_id' => auth()->id(),
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
            $submission->student->name . '_submission.' . pathinfo($submission->attachment_path, PATHINFO_EXTENSION)
        );
    }
}
