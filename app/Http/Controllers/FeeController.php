<?php

namespace App\Http\Controllers;

use App\Facades\Tenant;
use App\Models\AcademicSession;
use App\Models\Fee;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    /** Students with a fee summary, to track who has paid what (search + class filter). */
    public function students(Request $request)
    {
        $this->can($request, 'fees.view');

        $sessions = \App\Models\AcademicSession::orderByDesc('is_current')->orderByDesc('start_date')->get();
        $classes = \App\Models\SchoolClass::orderBy('name')->get();

        $query = Student::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->string('search');
                $q->where(fn ($w) => $w->where('first_name', 'like', "%{$s}%")->orWhere('last_name', 'like', "%{$s}%")->orWhere('admission_number', 'like', "%{$s}%"));
            })
            ->when($request->filled('class_id'), fn ($q) => $q->whereHas('enrollments', fn ($e) => $e->where('school_class_id', $request->class_id)->where('status', 'active')))
            ->withSum('fees as billed_sum', 'amount')
            ->withSum('fees as fine_sum', 'fine')
            ->withSum('fees as discount_sum', 'discount')
            ->withSum('fees as waived_sum', 'waived_amount')
            ->withSum('fees as paid_sum', 'paid_amount')
            ->orderBy('first_name')->orderBy('last_name')
            ->paginate(20)->withQueryString();

        return view('fees.students', compact('query', 'classes', 'sessions'));
    }

    /** One student's fee ledger — every period, paid or not, grouped by fee head. */
    public function student(Request $request, Student $student)
    {
        $this->can($request, 'fees.view');
        abort_if($student->school_id !== Tenant::getSchoolId(), 404);

        $fees = $student->fees()->with('schoolClass')->orderBy('due_date')->orderBy('period_no')->get();
        $groups = $fees->groupBy(fn ($f) => $f->title . ($f->fee_structure_id ? ' #' . $f->fee_structure_id : ''));

        $totals = [
            'billed' => $fees->sum(fn ($f) => $f->payable),
            'paid' => $fees->sum(fn ($f) => (float) $f->paid_amount),
        ];
        $totals['balance'] = round($totals['billed'] - $totals['paid'], 2);

        return view('fees.student', compact('student', 'groups', 'totals'));
    }

    /** Printable A4 fee statement with all of a student's installments on one page. */
    public function studentPrint(Request $request, Student $student)
    {
        $this->can($request, 'fees.view');
        abort_if($student->school_id !== Tenant::getSchoolId(), 404);

        $student->load(['enrollments' => fn ($q) => $q->where('status', 'active')->with(['schoolClass', 'section'])]);

        $fees = $student->fees()->with('schoolClass')->orderBy('due_date')->orderBy('period_no')->get();
        $groups = $fees->groupBy(fn ($f) => $f->title . ($f->fee_structure_id ? ' #' . $f->fee_structure_id : ''));

        $totals = [
            'billed' => $fees->sum(fn ($f) => (float) $f->amount + (float) $f->fine - (float) $f->discount),
            'waived' => $fees->sum(fn ($f) => (float) $f->waived_amount),
            'paid' => $fees->sum(fn ($f) => (float) $f->paid_amount),
        ];
        $totals['balance'] = round($totals['billed'] - $totals['waived'] - $totals['paid'], 2);

        return view('fees.student-print', [
            'student' => $student,
            'groups' => $groups,
            'totals' => $totals,
            'school' => Tenant::getSchool(),
        ]);
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

    /** Collect several installments at once (e.g. 3–4 months' advance), full balance of each. */
    public function bulkCollect(Request $request)
    {
        $this->can($request, 'fees.collect');

        $validated = $request->validate([
            'fee_ids' => 'required|array|min:1',
            'fee_ids.*' => 'integer',
            'paid_on' => 'required|date',
            'method' => 'nullable|string|max:50',
        ]);

        $fees = Fee::whereIn('id', $validated['fee_ids'])->where('school_id', Tenant::getSchoolId())->get();

        $count = 0;
        $total = 0.0;
        DB::transaction(function () use ($fees, $validated, &$count, &$total) {
            foreach ($fees as $fee) {
                if ($fee->balance <= 0) {
                    continue;
                }
                $total += $fee->balance;
                $fee->paid_amount = round((float) $fee->paid_amount + $fee->balance, 2);
                $fee->paid_on = $validated['paid_on'];
                $fee->method = $validated['method'] ?? $fee->method;
                $fee->syncStatus();
                $fee->save();
                $count++;
            }
        });

        return back()->with('success', $count > 0
            ? "Collected {$count} installment(s), total " . number_format($total, 2) . '.'
            : 'Nothing to collect in the selected rows.');
    }

    /** Waive several installments at once, full balance of each, one reason. */
    public function bulkWaive(Request $request)
    {
        $this->can($request, 'fees.refund');

        $validated = $request->validate([
            'fee_ids' => 'required|array|min:1',
            'fee_ids.*' => 'integer',
            'reason' => 'required|string|max:255',
        ], ['reason.required' => 'Please give a reason for waiving.']);

        $fees = Fee::whereIn('id', $validated['fee_ids'])->where('school_id', Tenant::getSchoolId())->get();

        $count = 0;
        $total = 0.0;
        DB::transaction(function () use ($fees, $validated, &$count, &$total) {
            foreach ($fees as $fee) {
                if ($fee->balance <= 0) {
                    continue;
                }
                $total += $fee->balance;
                $fee->waived_amount = round((float) $fee->waived_amount + $fee->balance, 2);
                $fee->waive_reason = trim(($fee->waive_reason ? $fee->waive_reason . '; ' : '') . $validated['reason']);
                $fee->syncStatus();
                $fee->save();
                $count++;
            }
        });

        return back()->with('success', $count > 0
            ? "Waived {$count} installment(s), total " . number_format($total, 2) . '.'
            : 'Nothing to waive in the selected rows.');
    }

    /** Waive (maaf) part or all of the outstanding balance, with a reason. */
    public function waive(Request $request, Fee $fee)
    {
        $this->can($request, 'fees.refund');
        abort_if($fee->school_id !== Tenant::getSchoolId(), 404);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . max(0.01, $fee->balance),
            'reason' => 'required|string|max:255',
        ], [
            'amount.max' => 'You can waive at most the outstanding balance (' . number_format($fee->balance, 2) . ').',
            'reason.required' => 'Please give a reason for waiving the fee.',
        ]);

        $fee->waived_amount = round((float) $fee->waived_amount + (float) $validated['amount'], 2);
        $fee->waive_reason = trim(($fee->waive_reason ? $fee->waive_reason . '; ' : '') . $validated['reason']);
        $fee->syncStatus();
        $fee->save();

        return redirect()->route('fees.show', $fee)->with('success', 'Fee waived: ' . number_format($validated['amount'], 2) . '.');
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
