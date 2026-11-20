<?php

namespace App\Http\Controllers;

use App\Facades\Tenant;
use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Links and unlinks existing guardians to a student (e.g. siblings sharing a parent).
 */
class StudentGuardianController extends Controller
{
    public function store(Request $request, Student $student): RedirectResponse
    {
        $schoolId = Tenant::getSchoolId();
        abort_if($student->school_id !== $schoolId, 404);

        $validated = $request->validate([
            'guardian_id' => [
                'required',
                'integer',
                Rule::exists('guardians', 'id')->where('school_id', $schoolId),
            ],
            'relationship' => 'required|string|max:50',
            'is_emergency_contact' => 'nullable|boolean',
        ], [
            'guardian_id.required' => 'Please choose a guardian.',
            'guardian_id.exists' => 'That guardian was not found in this school.',
        ]);

        if ($student->guardians()->whereKey($validated['guardian_id'])->exists()) {
            return back()
                ->withErrors(['guardian_id' => 'This guardian is already linked to ' . $student->full_name . '.'])
                ->withInput();
        }

        $student->guardians()->attach($validated['guardian_id'], [
            'relationship' => $validated['relationship'],
            'is_emergency_contact' => $request->boolean('is_emergency_contact'),
        ]);

        return redirect()
            ->route('students.show', ['student' => $student, 'tab' => 'guardians'])
            ->with('success', 'Guardian linked successfully.');
    }

    public function destroy(Student $student, Guardian $guardian): RedirectResponse
    {
        $schoolId = Tenant::getSchoolId();
        abort_if($student->school_id !== $schoolId || $guardian->school_id !== $schoolId, 404);

        $student->guardians()->detach($guardian->id);

        return redirect()
            ->route('students.show', ['student' => $student, 'tab' => 'guardians'])
            ->with('success', 'Guardian removed from this student. Their record is kept.');
    }
}
