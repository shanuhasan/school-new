<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Http\Request;
use App\Facades\Tenant;

class GuardianController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Same search is used by the Excel export (GuardianExportController)
        $query = Guardian::query()->filter($request->only('search'));

        $guardians = $query->latest()->paginate(15)->withQueryString();

        return view('guardians.index', compact('guardians'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $student = null;
        $existingGuardians = collect();

        // ?student={uuid}: the student's public GUID, never the numeric id
        if ($request->filled('student')) {
            $student = Student::where('uuid', (string) $request->input('student'))->firstOrFail();
            abort_if($student->school_id !== Tenant::getSchoolId(), 404);

            // Guardians of this school not yet linked to the student (e.g. a sibling's parent)
            $existingGuardians = Guardian::where('school_id', Tenant::getSchoolId())
                ->whereDoesntHave('students', fn ($q) => $q->whereKey($student->id))
                ->with('students:id,first_name,last_name')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get();
        }

        return view('guardians.create', compact('student', 'existingGuardians'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'occupation' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'student_id' => 'nullable|exists:students,id',
            'relationship' => 'required_with:student_id|string|max:50',
            'is_emergency_contact' => 'nullable|boolean',
        ]);

        $validated['school_id'] = Tenant::getSchoolId();

        // Avoid duplicate guardian records: if this phone is already on file, point the user to it.
        if ($request->filled('student_id') && ! $request->boolean('confirm_duplicate')) {
            $match = Guardian::where('school_id', $validated['school_id'])
                ->where('phone', $validated['phone'])
                ->first();

            if ($match) {
                return back()
                    ->withInput()
                    ->with('duplicate_guardian_id', $match->id)
                    ->withErrors(['phone' => "{$match->full_name} already uses this phone number. Link the existing guardian instead, or save again to create a separate record."]);
            }
        }

        $guardian = Guardian::create($validated);

        if ($request->filled('student_id')) {
            $student = Student::findOrFail($request->student_id);
            $student->guardians()->attach($guardian->id, [
                'relationship' => $validated['relationship'],
                'is_emergency_contact' => $request->boolean('is_emergency_contact'),
            ]);
            return redirect()->route('students.show', ['student' => $student, 'tab' => 'guardians'])->with('success', 'Guardian added successfully.');
        }

        return redirect()->route('guardians.index')->with('success', 'Guardian created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Guardian $guardian)
    {
        abort_if($guardian->school_id !== Tenant::getSchoolId(), 404);
        $guardian->load(['students' => fn ($q) => $q->orderBy('first_name')->with(['enrollments.schoolClass', 'enrollments.section'])]);
        return view('guardians.show', compact('guardian'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Guardian $guardian)
    {
        abort_if($guardian->school_id !== Tenant::getSchoolId(), 404);
        return view('guardians.edit', compact('guardian'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Guardian $guardian)
    {
        abort_if($guardian->school_id !== Tenant::getSchoolId(), 404);
        
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'occupation' => 'nullable|string|max:255',
            'address' => 'nullable|string',
        ]);

        $guardian->update($validated);

        return redirect()->route('guardians.index')->with('success', 'Guardian updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Guardian $guardian)
    {
        abort_if($guardian->school_id !== Tenant::getSchoolId(), 404);
        $guardian->delete();
        return back()->with('success', 'Guardian deleted successfully.');
    }
}
