<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Homework;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use App\Events\HomeworkCreated;

class HomeworkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Homework::class);
        
        $user = auth()->user();
        
        $sessionId = \App\Models\AcademicSession::where('is_current', true)->where('school_id', \App\Facades\Tenant::getSchoolId())->value('id');
        $query = Homework::with(['schoolClass', 'section', 'subject', 'teacher'])
                         ->where('academic_session_id', $sessionId);
                         
        if ($user->hasRole('Teacher')) {
            $query->where('teacher_id', $user->id);
        } elseif ($user->hasRole('Student')) {
            $profile = $user->studentProfile;
            if ($profile) {
                $sessionId = \App\Models\AcademicSession::where('is_current', true)->where('school_id', \App\Facades\Tenant::getSchoolId())->value('id');
                $enrollment = $profile->enrollments()->where('academic_session_id', $sessionId)->first();
                if ($enrollment) {
                    $query->where('school_class_id', $enrollment->school_class_id)
                          ->where('section_id', $enrollment->section_id);
                } else {
                    $query->whereRaw('1 = 0');
                }
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($user->hasRole('Parent')) {
            // Get all children's classes/sections
            $children = $user->children();
            $sessionId = \App\Models\AcademicSession::where('is_current', true)->where('school_id', \App\Facades\Tenant::getSchoolId())->value('id');
            $query->where(function($q) use ($children, $sessionId) {
                foreach ($children->get() as $child) {
                    $profile = $child->studentProfile;
                    if ($profile) {
                        $enrollment = $profile->enrollments()->where('academic_session_id', $sessionId)->first();
                        if ($enrollment) {
                            $q->orWhere(function($sq) use ($enrollment) {
                                $sq->where('school_class_id', $enrollment->school_class_id)
                                   ->where('section_id', $enrollment->section_id);
                            });
                        }
                    }
                }
            });
        }
        
        // Filter by class/section (for admins/teachers)
        if ($request->filled('school_class_id')) {
            $query->where('school_class_id', $request->school_class_id);
        }
        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }
        
        $homeworks = $query->orderBy('due_date', 'desc')->paginate(15);
        $classes = SchoolClass::all();
        
        return view('academic.homework.index', compact('homeworks', 'classes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', Homework::class);
        
        $classes = SchoolClass::all();
        $subjects = Subject::all();
        
        return view('academic.homework.create', compact('classes', 'subjects'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Homework::class);
        
        $validated = $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'homework_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:homework_date',
            'attachment' => 'nullable|file|max:10240', // 10MB max
        ]);

        $homework = new Homework($validated);
        $homework->school_id = \App\Facades\Tenant::getSchoolId() ?? auth()->user()->current_school_id;
        $sessionId = \App\Models\AcademicSession::where('is_current', true)->where('school_id', \App\Facades\Tenant::getSchoolId())->value('id');
        $homework->academic_session_id = $sessionId;
        $homework->teacher_id = auth()->id();

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('homeworks', 'local');
            $homework->attachment_path = $path;
        }

        $homework->save();

        event(new HomeworkCreated($homework));

        return redirect()->route('academic.homework.index')->with('success', 'Homework created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Homework $homework)
    {
        Gate::authorize('view', $homework);
        
        $homework->load(['schoolClass', 'section', 'subject', 'teacher', 'submissions.student']);
        
        return view('academic.homework.show', compact('homework'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Homework $homework)
    {
        Gate::authorize('update', $homework);
        
        $classes = SchoolClass::all();
        $sections = Section::where('school_class_id', $homework->school_class_id)->get();
        $subjects = Subject::all();
        
        return view('academic.homework.edit', compact('homework', 'classes', 'sections', 'subjects'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Homework $homework)
    {
        Gate::authorize('update', $homework);
        
        $validated = $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'homework_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:homework_date',
            'attachment' => 'nullable|file|max:10240', // 10MB max
        ]);

        if ($request->hasFile('attachment')) {
            // Delete old file if exists
            if ($homework->attachment_path) {
                Storage::disk('local')->delete($homework->attachment_path);
            }
            $path = $request->file('attachment')->store('homeworks', 'local');
            $validated['attachment_path'] = $path;
        }

        $homework->update($validated);

        return redirect()->route('academic.homework.show', $homework)->with('success', 'Homework updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Homework $homework)
    {
        Gate::authorize('delete', $homework);
        
        if ($homework->attachment_path) {
            Storage::disk('local')->delete($homework->attachment_path);
        }
        
        $homework->delete();
        
        return redirect()->route('academic.homework.index')->with('success', 'Homework deleted successfully.');
    }
    
    /**
     * Download attachment
     */
    public function downloadAttachment(Homework $homework)
    {
        Gate::authorize('view', $homework);
        
        if (!$homework->attachment_path || !Storage::disk('local')->exists($homework->attachment_path)) {
            abort(404, 'Attachment not found.');
        }
        
        return Storage::disk('local')->download($homework->attachment_path, $homework->title . '_attachment.' . pathinfo($homework->attachment_path, PATHINFO_EXTENSION));
    }
}
