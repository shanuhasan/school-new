<?php

namespace App\Http\Controllers;

use App\Facades\Tenant;
use App\Models\AcademicSession;
use App\Models\Fee;
use App\Models\Student;
use Illuminate\Http\Request;

class FeeController extends Controller
{
    private function can(Request $request, string $ability): void
    {
        abort_unless($request->user()->can($ability), 403);
    }

    public function index(Request $request)
    {
        $this->can($request, 'fees.view');

        $fees = Fee::with('student')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(fn ($w) => $w->where('title', 'like', "%{$search}%")
                    ->orWhereHas('student', fn ($s) => $s->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('admission_number', 'like', "%{$search}%")));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'outstanding' => (float) Fee::where('status', '!=', 'paid')->sum('amount')
                + (float) Fee::where('status', '!=', 'paid')->sum('fine')
                - (float) Fee::where('status', '!=', 'paid')->sum('discount')
                - (float) Fee::where('status', '!=', 'paid')->sum('paid_amount'),
            'collected' => (float) Fee::sum('paid_amount'),
            'unpaid' => Fee::where('status', '!=', 'paid')->count(),
        ];

        return view('fees.index', compact('fees', 'summary'));
    }

    public function create(Request $request)
    {
        $this->can($request, 'fees.create');

        return view('fees.create', [
            'students' => Student::orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'admission_number']),
            'sessions' => AcademicSession::orderByDesc('is_current')->orderByDesc('start_date')->get(),
            'fee' => null,
        ]);
    }

    public function store(Request $request)
    {
        $this->can($request, 'fees.create');

        $data = $this->validateFee($request);

        $fee = new Fee($data + ['school_id' => Tenant::getSchoolId(), 'created_by' => $request->user()->id]);
        $fee->paid_amount = 0;
        $fee->syncStatus();
        $fee->save();

        return redirect()->route('fees.show', $fee)->with('success', 'Fee created.');
    }

    public function show(Request $request, Fee $fee)
    {
        $this->can($request, 'fees.view');
        abort_if($fee->school_id !== Tenant::getSchoolId(), 404);

        $fee->load(['student', 'session']);

        return view('fees.show', compact('fee'));
    }

    /** Record a payment against the fee. */
    public function collect(Request $request, Fee $fee)
    {
        $this->can($request, 'fees.collect');
        abort_if($fee->school_id !== Tenant::getSchoolId(), 404);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . max(0.01, $fee->balance),
            'paid_on' => 'required|date',
            'method' => 'nullable|string|max:50',
            'remarks' => 'nullable|string|max:255',
        ], [
            'amount.max' => 'The amount cannot be more than the outstanding balance (' . number_format($fee->balance, 2) . ').',
        ]);

        $fee->paid_amount = round((float) $fee->paid_amount + (float) $validated['amount'], 2);
        $fee->paid_on = $validated['paid_on'];
        $fee->method = $validated['method'] ?? $fee->method;
        if (! empty($validated['remarks'])) {
            $fee->remarks = $validated['remarks'];
        }
        $fee->syncStatus();
        $fee->save();

        return redirect()->route('fees.show', $fee)->with('success', 'Payment recorded.');
    }

    public function slip(Request $request, Fee $fee)
    {
        $this->can($request, 'fees.view');
        abort_if($fee->school_id !== Tenant::getSchoolId(), 404);

        $fee->load(['student', 'session']);

        return view('fees.slip', ['fee' => $fee, 'school' => Tenant::getSchool()]);
    }

    public function destroy(Request $request, Fee $fee)
    {
        $this->can($request, 'fees.update');
        abort_if($fee->school_id !== Tenant::getSchoolId(), 404);

        $fee->delete();

        return redirect()->route('fees.index')->with('success', 'Fee deleted.');
    }

    private function validateFee(Request $request): array
    {
        $schoolId = Tenant::getSchoolId();

        return $request->validate([
            'student_id' => ['required', \Illuminate\Validation\Rule::exists('students', 'id')->where('school_id', $schoolId)],
            'academic_session_id' => ['nullable', \Illuminate\Validation\Rule::exists('academic_sessions', 'id')->where('school_id', $schoolId)],
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'fine' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
            'remarks' => 'nullable|string|max:500',
        ]);
    }
}
