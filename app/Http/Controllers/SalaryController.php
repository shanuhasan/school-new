<?php

namespace App\Http\Controllers;

use App\Facades\Tenant;
use App\Models\SalaryPayment;
use App\Models\Staff;
use App\Models\Teacher;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
    private function can(Request $request, string $ability): void
    {
        abort_unless($request->user()->can($ability), 403);
    }

    public function index(Request $request)
    {
        $this->can($request, 'salary.view');

        $salaries = SalaryPayment::query()
            ->when($request->filled('month'), fn ($q) => $q->where('month', $request->month))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), fn ($q) => $q->where('employee_name', 'like', '%' . $request->string('search') . '%'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'paid' => (float) SalaryPayment::where('status', 'paid')->sum('net_salary'),
            'pending' => SalaryPayment::where('status', 'pending')->count(),
        ];

        return view('salaries.index', compact('salaries', 'summary'));
    }

    public function create(Request $request)
    {
        $this->can($request, 'salary.manage');

        return view('salaries.create', [
            'employees' => $this->employees(),
            'month' => $request->old('month', now()->format('Y-m')),
        ]);
    }

    public function store(Request $request)
    {
        $this->can($request, 'salary.manage');

        $validated = $request->validate([
            'user_id' => ['nullable', \Illuminate\Validation\Rule::exists('users', 'id')],
            'employee_name' => 'required|string|max:255',
            'designation' => 'nullable|string|max:255',
            'month' => 'required|date_format:Y-m',
            'basic' => 'required|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
            'deductions' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:500',
        ]);

        $net = round((float) $validated['basic'] + (float) ($validated['allowances'] ?? 0) - (float) ($validated['deductions'] ?? 0), 2);

        $salary = SalaryPayment::create($validated + [
            'school_id' => Tenant::getSchoolId(),
            'allowances' => $validated['allowances'] ?? 0,
            'deductions' => $validated['deductions'] ?? 0,
            'net_salary' => $net,
            'status' => 'pending',
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('salaries.show', $salary)->with('success', 'Payslip created.');
    }

    public function show(Request $request, SalaryPayment $salary)
    {
        $this->can($request, 'salary.view');
        abort_if($salary->school_id !== Tenant::getSchoolId(), 404);

        return view('salaries.show', compact('salary'));
    }

    public function pay(Request $request, SalaryPayment $salary)
    {
        $this->can($request, 'salary.manage');
        abort_if($salary->school_id !== Tenant::getSchoolId(), 404);

        $validated = $request->validate([
            'paid_on' => 'required|date',
            'method' => 'nullable|string|max:50',
        ]);

        $salary->update([
            'status' => 'paid',
            'paid_on' => $validated['paid_on'],
            'method' => $validated['method'] ?? $salary->method,
        ]);

        return redirect()->route('salaries.show', $salary)->with('success', 'Marked as paid.');
    }

    public function slip(Request $request, SalaryPayment $salary)
    {
        $this->can($request, 'salary.view');
        abort_if($salary->school_id !== Tenant::getSchoolId(), 404);

        return view('salaries.slip', ['salary' => $salary, 'school' => Tenant::getSchool()]);
    }

    public function destroy(Request $request, SalaryPayment $salary)
    {
        $this->can($request, 'salary.manage');
        abort_if($salary->school_id !== Tenant::getSchoolId(), 404);

        $salary->delete();

        return redirect()->route('salaries.index')->with('success', 'Payslip deleted.');
    }

    /** Teachers and general staff of the current school, for the employee picker. */
    private function employees()
    {
        $teachers = Teacher::forCurrentSchool()->with(['user:id,name', 'designation:id,name'])->get()
            ->map(fn ($t) => [
                'user_id' => $t->user_id,
                'name' => $t->user?->name,
                'designation' => $t->designation?->name ?? 'Teacher',
            ]);

        $staff = Staff::forCurrentSchool()->with(['user:id,name', 'designation:id,name'])->get()
            ->map(fn ($s) => [
                'user_id' => $s->user_id,
                'name' => $s->user?->name,
                'designation' => $s->designation?->name ?? 'Staff',
            ]);

        return $teachers->concat($staff)->filter(fn ($e) => $e['name'])->sortBy('name')->values();
    }
}
