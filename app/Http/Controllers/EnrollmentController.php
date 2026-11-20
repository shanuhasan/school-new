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
        abort_if($student->school_id !== Tenant::getSchoolId(), 404);

        $validated = $request->validateWithBag('enrollment', [
            'academic_session_id' => [
                'required',
                Rule::exists('academic_sessions', 'id')->where('school_id', Tenant::getSchoolId()),
                Rule::unique('enrollments')->where('student_id', $student->id),
            ],
            ...$this->classSectionRules($request),
            'roll_number' => 'nullable|string|max:50',
            'status' => 'required|in:active,promoted,dropped',
        ], $this->messages() + [
            'academic_session_id.unique' => 'This student is already enrolled in the selected academic session.',
        ]);

        $validated['school_id'] = Tenant::getSchoolId();

        $student->enrollments()->create($validated);

        return redirect()
            ->route('students.show', ['student' => $student, 'tab' => 'enrollments'])
            ->with('success', 'Student enrolled successfully.');
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
            ...$this->classSectionRules($request),
            'roll_number' => 'nullable|string|max:50',
            'status' => 'required|in:active,promoted,dropped',
        ], $this->messages());

        $enrollment->update($validated);

        return back()->with('success', 'Enrollment updated successfully.');
    }

    /**
     * Class must belong to the current school; section (optional) must belong to that class.
     */
    private function classSectionRules(Request $request): array
    {
        return [
            'school_class_id' => [
                'required',
                Rule::exists('school_classes', 'id')->where('school_id', Tenant::getSchoolId()),
            ],
            'section_id' => [
                'nullable',
                Rule::exists('sections', 'id')->where('school_class_id', (int) $request->input('school_class_id')),
            ],
        ];
    }

    private function messages(): array
    {
        return [
            'school_class_id.exists' => 'Please choose a class from this school.',
            'section_id.exists' => 'The selected section does not belong to the chosen class.',
        ];
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
