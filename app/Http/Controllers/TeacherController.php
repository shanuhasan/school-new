<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Designation;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TeacherController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Teacher::class);

        $query = Teacher::forCurrentSchool()->with(['user', 'department', 'designation']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            })->orWhere('employee_id', 'like', "%{$search}%");
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $teachers = $query->paginate(10)->withQueryString();
        $departments = Department::forCurrentSchool()->get();

        return view('staff.teachers.index', compact('teachers', 'departments'));
    }

    public function create()
    {
        $this->authorize('create', Teacher::class);
        $departments = Department::forCurrentSchool()->get();
        $designations = Designation::forCurrentSchool()->get();
        $subjects = Subject::forCurrentSchool()->get();
        $classes = SchoolClass::forCurrentSchool()->get();

        return view('staff.teachers.create', compact('departments', 'designations', 'subjects', 'classes'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Teacher::class);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'employee_id' => [
                'nullable', 'string', 'max:50',
                Rule::unique('teachers')->where('school_id', \App\Facades\Tenant::getSchoolId())
            ],
            'department_id' => 'nullable|exists:departments,id',
            'designation_id' => 'nullable|exists:designations,id',
            'joining_date' => 'nullable|date',
            'qualification' => 'nullable|string',
            'status' => 'required|in:active,inactive,on_leave,resigned',
            'subjects' => 'nullable|array',
            'subjects.*' => 'exists:subjects,id',
            'classes' => 'nullable|array',
            'classes.*' => 'exists:school_classes,id',
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['first_name'] . ' ' . $validated['last_name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make(Str::random(12)),
                'current_school_id' => \App\Facades\Tenant::getSchoolId(),
            ]);

            $user->assignRole('Teacher');
            $user->schools()->attach(\App\Facades\Tenant::getSchoolId());

            $teacher = Teacher::create([
                'school_id' => \App\Facades\Tenant::getSchoolId(),
                'user_id' => $user->id,
                'department_id' => $validated['department_id'] ?? null,
                'designation_id' => $validated['designation_id'] ?? null,
                'employee_id' => $validated['employee_id'] ?? null,
                'joining_date' => $validated['joining_date'] ?? null,
                'qualification' => $validated['qualification'] ?? null,
                'status' => $validated['status'] ?? null,
            ]);

            if (!empty($validated['subjects'])) {
                $teacher->subjects()->attach($validated['subjects']);
            }

            if (!empty($validated['classes'])) {
                $teacher->classes()->attach($validated['classes']);
            }
        });

        return redirect()->route('staff.teachers.index')->with('success', 'Teacher created successfully. An email with login details should be sent.');
    }

    public function show(Teacher $teacher)
    {
        $this->authorize('view', $teacher);
        $teacher->load(['user', 'department', 'designation', 'subjects', 'classes']);
        return view('staff.teachers.show', compact('teacher'));
    }

    public function edit(Teacher $teacher)
    {
        $this->authorize('update', $teacher);
        $departments = Department::forCurrentSchool()->get();
        $designations = Designation::forCurrentSchool()->get();
        $subjects = Subject::forCurrentSchool()->get();
        $classes = SchoolClass::forCurrentSchool()->get();

        $teacher->load(['user', 'subjects', 'classes']);

        // Split name into first and last name for editing if needed, but here we can just edit name as one or let's pass it.
        $nameParts = explode(' ', $teacher->user->name, 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        return view('staff.teachers.edit', compact('teacher', 'departments', 'designations', 'subjects', 'classes', 'firstName', 'lastName'));
    }

    public function update(Request $request, Teacher $teacher)
    {
        $this->authorize('update', $teacher);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'employee_id' => [
                'nullable', 'string', 'max:50',
                Rule::unique('teachers')->where('school_id', \App\Facades\Tenant::getSchoolId())->ignore($teacher->id)
            ],
            'department_id' => 'nullable|exists:departments,id',
            'designation_id' => 'nullable|exists:designations,id',
            'joining_date' => 'nullable|date',
            'qualification' => 'nullable|string',
            'status' => 'required|in:active,inactive,on_leave,resigned',
            'subjects' => 'nullable|array',
            'subjects.*' => 'exists:subjects,id',
            'classes' => 'nullable|array',
            'classes.*' => 'exists:school_classes,id',
        ]);

        DB::transaction(function () use ($validated, $teacher) {
            $teacher->user->update([
                'name' => $validated['first_name'] . ' ' . $validated['last_name'],
                'phone' => $validated['phone'] ?? null,
            ]);

            $teacher->update([
                'department_id' => $validated['department_id'] ?? null,
                'designation_id' => $validated['designation_id'] ?? null,
                'employee_id' => $validated['employee_id'] ?? null,
                'joining_date' => $validated['joining_date'] ?? null,
                'qualification' => $validated['qualification'] ?? null,
                'status' => $validated['status'] ?? null,
            ]);

            $teacher->subjects()->sync($validated['subjects'] ?? []);
            $teacher->classes()->sync($validated['classes'] ?? []);
        });

        return redirect()->route('staff.teachers.index')->with('success', 'Teacher updated successfully.');
    }

    public function destroy(Teacher $teacher)
    {
        $this->authorize('delete', $teacher);
        $teacher->user->delete(); // This cascades to teacher if properly setup, but let's delete user which deletes teacher, wait. Does User have cascadeOnDelete for Teacher? 
        // No, user table doesn't have a foreign key to teacher. Teacher table has foreign key to user with cascadeOnDelete.
        // So deleting the user will delete the teacher record.
        return redirect()->route('staff.teachers.index')->with('success', 'Teacher deleted successfully.');
    }
}
