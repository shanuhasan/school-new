<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SchoolClassController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', SchoolClass::class);

        $query = SchoolClass::forCurrentSchool();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $classes = $query->orderBy('level')->paginate(10)->withQueryString();

        return view('academic.classes.index', compact('classes'));
    }

    public function create()
    {
        $this->authorize('create', SchoolClass::class);
        return view('academic.classes.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', SchoolClass::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'level' => 'nullable|integer',
        ]);

        $validated['school_id'] = \App\Facades\Tenant::getSchoolId();

        SchoolClass::create($validated);

        return redirect()->route('academic.classes.index')->with('success', 'Class created successfully.');
    }

    public function show(SchoolClass $class)
    {
        $this->authorize('view', $class);
        $class->load('sections', 'subjects');
        return view('academic.classes.show', compact('class'));
    }

    public function edit(SchoolClass $class)
    {
        $this->authorize('update', $class);
        return view('academic.classes.edit', compact('class'));
    }

    public function update(Request $request, SchoolClass $class)
    {
        $this->authorize('update', $class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'level' => 'nullable|integer',
        ]);

        $class->update($validated);

        return redirect()->route('academic.classes.index')->with('success', 'Class updated successfully.');
    }

    public function destroy(SchoolClass $class)
    {
        $this->authorize('delete', $class);
        $class->delete();
        return redirect()->route('academic.classes.index')->with('success', 'Class deleted successfully.');
    }
}
