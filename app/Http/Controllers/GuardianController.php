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
        $query = Guardian::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $guardians = $query->latest()->paginate(15)->withQueryString();

        return view('guardians.index', compact('guardians'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $student = null;
        if ($request->filled('student_id')) {
            $student = Student::findOrFail($request->student_id);
        }
        return view('guardians.create', compact('student'));
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

        $guardian = Guardian::create($validated);

        if ($request->filled('student_id')) {
            $student = Student::findOrFail($request->student_id);
            $student->guardians()->attach($guardian->id, [
                'relationship' => $validated['relationship'],
                'is_emergency_contact' => $request->boolean('is_emergency_contact'),
            ]);
            return redirect()->route('students.show', $student)->with('success', 'Guardian added successfully.');
        }

        return redirect()->route('guardians.index')->with('success', 'Guardian created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Guardian $guardian)
    {
        abort_if($guardian->school_id !== Tenant::getSchoolId(), 404);
        $guardian->load('students');
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
