<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\AcademicSession;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Facades\Tenant;
use App\Traits\HandlesImageUploads;

class StudentController extends Controller
{
    use HandlesImageUploads;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Same filters are used by the Excel export (StudentExportController)
        $query = Student::query()->filter($request->only(['search', 'class_id', 'section_id', 'session_id', 'status']));

        $students = $query->latest()->paginate(15)->withQueryString();
        
        $classes = SchoolClass::all();
        $sessions = AcademicSession::all();

        return view('students.index', compact('students', 'classes', 'sessions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $sessions = AcademicSession::forCurrentSchool()->orderByDesc('is_current')->orderByDesc('start_date')->get();
        $classes = SchoolClass::forCurrentSchool()->with(['sections' => fn ($q) => $q->orderBy('name')])->orderBy('name')->get();

        return view('students.create', compact('sessions', 'classes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $schoolId = Tenant::getSchoolId();

        $validated = $request->validate([
            'admission_number' => [
                'required',
                'string',
                Rule::unique('students')->where('school_id', $schoolId),
            ],
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female,other',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'blood_group' => 'nullable|string|max:10',
            'admission_date' => 'required|date',
            'status' => 'required|in:active,inactive',
            'photo' => 'nullable|image|max:2048',
            // Optional enrollment at admission: class + session go together; section & roll are optional.
            'school_class_id' => ['nullable', 'required_with:academic_session_id', Rule::exists('school_classes', 'id')->where('school_id', $schoolId)],
            'academic_session_id' => ['nullable', 'required_with:school_class_id', Rule::exists('academic_sessions', 'id')->where('school_id', $schoolId)],
            'section_id' => ['nullable', Rule::exists('sections', 'id')->where('school_class_id', (int) $request->input('school_class_id'))],
            'roll_number' => 'nullable|string|max:50',
        ], [
            'school_class_id.required_with' => 'Choose a class to enrol the student.',
            'academic_session_id.required_with' => 'Choose a session to enrol the student.',
            'section_id.exists' => 'The selected section does not belong to the chosen class.',
        ]);

        // Enrollment fields aren't part of the student record
        $enrollment = \Illuminate\Support\Arr::only($validated, ['school_class_id', 'academic_session_id', 'section_id', 'roll_number']);
        $validated = \Illuminate\Support\Arr::except($validated, ['school_class_id', 'academic_session_id', 'section_id', 'roll_number']);

        if ($request->hasFile('photo')) {
            $path = $this->uploadAndResizeImage($request->file('photo'), 'students/photos', 'public', 800, 800, 80);
            $validated['photo_path'] = $path;
        }

        $validated['school_id'] = $schoolId;

        $student = Student::create($validated);

        // Optional enrollment at admission
        if (! empty($enrollment['school_class_id'])) {
            $student->enrollments()->create([
                'school_id' => $schoolId,
                'academic_session_id' => $enrollment['academic_session_id'],
                'school_class_id' => $enrollment['school_class_id'],
                'section_id' => $enrollment['section_id'] ?: null,
                'roll_number' => $enrollment['roll_number'] ?: null,
                'status' => 'active',
            ]);
        }

        // Optional login account for the student
        if ($request->boolean('create_login')) {
            $request->validate(['email' => 'required|email'], [], ['email' => 'email']);
            try {
                $result = app(\App\Actions\CreateLoginAccount::class)->forStudent($student, $request->filled('login_password') ? $request->input('login_password') : null);
                session()->flash('new_login', $this->loginFlash($result, $student->full_name));
            } catch (\RuntimeException $e) {
                session()->flash('warning', 'Student saved, but the login account could not be created: ' . $e->getMessage());
            }
        }

        return redirect()->route('students.show', $student)->with('success', 'Student created successfully.');
    }

    /** Create a login account for an existing student (from the student profile). */
    public function createLoginAccount(Request $request, Student $student, \App\Actions\CreateLoginAccount $accounts)
    {
        abort_if($student->school_id !== Tenant::getSchoolId(), 404);

        $request->validate(['login_password' => 'nullable|string|min:8|max:255']);

        if (! $student->email) {
            return back()->with('error', 'Add an email address to this student first, then create their login.');
        }

        try {
            $result = $accounts->forStudent($student, $request->filled('login_password') ? $request->input('login_password') : null);

            if ($result['existed']) {
                return back()->with('error', 'This student already has a login account.');
            }

            return back()->with('new_login', $this->loginFlash($result, $student->full_name));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function loginFlash(array $result, string $name): array
    {
        return ['name' => $name, 'email' => $result['user']->email, 'password' => $result['password']];
    }

    /**
     * Display the specified resource.
     */
    public function show(Student $student)
    {
        abort_if($student->school_id !== Tenant::getSchoolId(), 404);
        $student->load(['guardians', 'enrollments.academicSession', 'enrollments.schoolClass', 'enrollments.section', 'documents']);
        return view('students.show', compact('student'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Student $student)
    {
        abort_if($student->school_id !== Tenant::getSchoolId(), 404);
        return view('students.edit', compact('student'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Student $student)
    {
        abort_if($student->school_id !== Tenant::getSchoolId(), 404);
        
        $validated = $request->validate([
            'admission_number' => [
                'required',
                'string',
                Rule::unique('students')->where('school_id', Tenant::getSchoolId())->ignore($student->id),
            ],
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female,other',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'blood_group' => 'nullable|string|max:10',
            'admission_date' => 'required|date',
            'status' => 'required|in:active,inactive,graduated,transferred,suspended',
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            if ($student->photo_path) {
                Storage::disk('public')->delete($student->photo_path);
            }
            $path = $this->uploadAndResizeImage($request->file('photo'), 'students/photos', 'public', 800, 800, 80);
            $validated['photo_path'] = $path;
        }

        $student->update($validated);

        return redirect()->route('students.show', $student)->with('success', 'Student updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student)
    {
        abort_if($student->school_id !== Tenant::getSchoolId(), 404);
        
        if ($student->photo_path) {
            Storage::disk('public')->delete($student->photo_path);
        }
        $student->delete();

        return redirect()->route('students.index')->with('success', 'Student deleted successfully.');
    }
}
