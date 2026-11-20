<?php

namespace App\Http\Controllers;

use App\Models\Homework;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SubjectController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Subject::class);

        $query = Subject::forCurrentSchool();

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%');
            });
        }

        $subjects = $query->orderBy('name')->paginate(10)->withQueryString();

        return view('academic.subjects.index', compact('subjects'));
    }

    public function create()
    {
        $this->authorize('create', Subject::class);
        return view('academic.subjects.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Subject::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
        ]);

        $validated['school_id'] = \App\Facades\Tenant::getSchoolId();
        Subject::create($validated);

        return redirect()->route('academic.subjects.index')->with('success', 'Subject created successfully.');
    }

    public function show(Subject $subject)
    {
        $this->authorize('view', $subject);

        $subject->load(['classes' => fn ($q) => $q->orderBy('name')]);

        $teachers = Teacher::forCurrentSchool()
            ->whereHas('subjects', fn ($q) => $q->whereKey($subject->id))
            ->with(['user', 'classes' => fn ($q) => $q->orderBy('name')])
            ->get()
            ->sortBy(fn ($t) => $t->user?->name)
            ->values();

        $homework = Homework::where('subject_id', $subject->id)
            ->with(['schoolClass', 'section'])
            ->orderByDesc('due_date')
            ->limit(6)
            ->get();

        return view('academic.subjects.show', compact('subject', 'teachers', 'homework'));
    }

    public function edit(Subject $subject)
    {
        $this->authorize('update', $subject);
        return view('academic.subjects.edit', compact('subject'));
    }

    public function update(Request $request, Subject $subject)
    {
        $this->authorize('update', $subject);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
        ]);

        $subject->update($validated);

        return redirect()->route('academic.subjects.index')->with('success', 'Subject updated successfully.');
    }

    public function destroy(Subject $subject)
    {
        $this->authorize('delete', $subject);
        $subject->delete();
        return redirect()->route('academic.subjects.index')->with('success', 'Subject deleted successfully.');
    }
}
