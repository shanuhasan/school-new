<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Facades\Tenant;

class EnrollmentController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Student $student)
    {
        $validated = $request->validate([
            'academic_session_id' => [
                'required',
                'exists:academic_sessions,id',
                Rule::unique('enrollments')->where('student_id', $student->id),
            ],
            'school_class_id' => 'required|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'roll_number' => 'nullable|string|max:50',
            'status' => 'required|in:active,promoted,dropped',
        ], [
            'academic_session_id.unique' => 'This student is already enrolled in the selected academic session.'
        ]);

        $validated['school_id'] = Tenant::getSchoolId();

        $student->enrollments()->create($validated);

        return back()->with('success', 'Student enrolled successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Student $student, Enrollment $enrollment)
    {
        // Ensure enrollment belongs to student
        if ($enrollment->student_id !== $student->id) {
            abort(403);
        }

        $validated = $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'roll_number' => 'nullable|string|max:50',
            'status' => 'required|in:active,promoted,dropped',
        ]);

        $enrollment->update($validated);

        return back()->with('success', 'Enrollment updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student, Enrollment $enrollment)
    {
        if ($enrollment->student_id !== $student->id) {
            abort(403);
        }
        
        $enrollment->delete();

        return back()->with('success', 'Enrollment removed successfully.');
    }
}
