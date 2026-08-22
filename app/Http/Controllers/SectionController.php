<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SectionController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Section::class);

        $schoolClasses = SchoolClass::forCurrentSchool()->pluck('id');
        $query = Section::whereIn('school_class_id', $schoolClasses)->with('schoolClass');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $sections = $query->orderBy('school_class_id')->paginate(10)->withQueryString();

        return view('academic.sections.index', compact('sections'));
    }

    public function create()
    {
        $this->authorize('create', Section::class);
        $classes = SchoolClass::forCurrentSchool()->get();
        return view('academic.sections.create', compact('classes'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Section::class);

        $validated = $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'name' => 'required|string|max:255',
        ]);

        // Ensure the selected class belongs to the current school
        $class = SchoolClass::forCurrentSchool()->findOrFail($validated['school_class_id']);

        Section::create($validated);

        return redirect()->route('academic.sections.index')->with('success', 'Section created successfully.');
    }

    public function edit(Section $section)
    {
        $this->authorize('update', $section);
        $classes = SchoolClass::forCurrentSchool()->get();
        return view('academic.sections.edit', compact('section', 'classes'));
    }

    public function update(Request $request, Section $section)
    {
        $this->authorize('update', $section);

        $validated = $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'name' => 'required|string|max:255',
        ]);

        $class = SchoolClass::forCurrentSchool()->findOrFail($validated['school_class_id']);

        $section->update($validated);

        return redirect()->route('academic.sections.index')->with('success', 'Section updated successfully.');
    }

    public function destroy(Section $section)
    {
        $this->authorize('delete', $section);
        $section->delete();
        return redirect()->route('academic.sections.index')->with('success', 'Section deleted successfully.');
    }
}
