<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class StaffController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Staff::class);

        $query = Staff::forCurrentSchool()->with(['user', 'department', 'designation']);

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

        $staffs = $query->paginate(10)->withQueryString();
        $departments = Department::forCurrentSchool()->get();

        return view('staff.staffs.index', compact('staffs', 'departments'));
    }

    public function create()
    {
        $this->authorize('create', Staff::class);
        $departments = Department::forCurrentSchool()->get();
        $designations = Designation::forCurrentSchool()->get();

        return view('staff.staffs.create', compact('departments', 'designations'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Staff::class);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'employee_id' => [
                'nullable', 'string', 'max:50',
                Rule::unique('staff')->where('school_id', \App\Facades\Tenant::getSchoolId())
            ],
            'department_id' => 'nullable|exists:departments,id',
            'designation_id' => 'nullable|exists:designations,id',
            'joining_date' => 'nullable|date',
            'qualification' => 'nullable|string',
            'status' => 'required|in:active,inactive,on_leave,resigned',
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['first_name'] . ' ' . $validated['last_name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make(Str::random(12)),
                'current_school_id' => \App\Facades\Tenant::getSchoolId(),
            ]);

            $user->assignRole('Staff');
            $user->schools()->attach(\App\Facades\Tenant::getSchoolId());

            Staff::create([
                'school_id' => \App\Facades\Tenant::getSchoolId(),
                'user_id' => $user->id,
                'department_id' => $validated['department_id'] ?? null,
                'designation_id' => $validated['designation_id'] ?? null,
                'employee_id' => $validated['employee_id'] ?? null,
                'joining_date' => $validated['joining_date'] ?? null,
                'qualification' => $validated['qualification'] ?? null,
                'status' => $validated['status'] ?? null,
            ]);
        });

        return redirect()->route('staff.staffs.index')->with('success', 'Staff created successfully.');
    }

    public function show(Staff $staff)
    {
        $this->authorize('view', $staff);
        $staff->load(['user', 'department', 'designation']);
        return view('staff.staffs.show', compact('staff'));
    }

    public function edit(Staff $staff)
    {
        $this->authorize('update', $staff);
        $departments = Department::forCurrentSchool()->get();
        $designations = Designation::forCurrentSchool()->get();

        $staff->load(['user']);

        $nameParts = explode(' ', $staff->user->name, 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        return view('staff.staffs.edit', compact('staff', 'departments', 'designations', 'firstName', 'lastName'));
    }

    public function update(Request $request, Staff $staff)
    {
        $this->authorize('update', $staff);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'employee_id' => [
                'nullable', 'string', 'max:50',
                Rule::unique('staff')->where('school_id', \App\Facades\Tenant::getSchoolId())->ignore($staff->id)
            ],
            'department_id' => 'nullable|exists:departments,id',
            'designation_id' => 'nullable|exists:designations,id',
            'joining_date' => 'nullable|date',
            'qualification' => 'nullable|string',
            'status' => 'required|in:active,inactive,on_leave,resigned',
        ]);

        DB::transaction(function () use ($validated, $staff) {
            $staff->user->update([
                'name' => $validated['first_name'] . ' ' . $validated['last_name'],
                'phone' => $validated['phone'] ?? null,
            ]);

            $staff->update([
                'department_id' => $validated['department_id'] ?? null,
                'designation_id' => $validated['designation_id'] ?? null,
                'employee_id' => $validated['employee_id'] ?? null,
                'joining_date' => $validated['joining_date'] ?? null,
                'qualification' => $validated['qualification'] ?? null,
                'status' => $validated['status'] ?? null,
            ]);
        });

        return redirect()->route('staff.staffs.index')->with('success', 'Staff updated successfully.');
    }

    public function destroy(Staff $staff)
    {
        $this->authorize('delete', $staff);
        $staff->user->delete();
        return redirect()->route('staff.staffs.index')->with('success', 'Staff deleted successfully.');
    }
}
