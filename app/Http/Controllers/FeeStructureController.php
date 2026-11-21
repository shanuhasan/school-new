<?php

namespace App\Http\Controllers;

use App\Facades\Tenant;
use App\Models\AcademicSession;
use App\Models\Enrollment;
use App\Models\Fee;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Class-wise fee setup. Define "Class I → Tuition ₹1000 monthly" once, then Generate the
 * per-student, per-month/quarter/year installments for everyone enrolled in that class.
 */
class FeeStructureController extends Controller
{
    private function can(Request $request, string $ability): void
    {
        abort_unless($request->user()->can($ability), 403);
    }

    public function index(Request $request)
    {
        $this->can($request, 'fees.view');

        $sessions = AcademicSession::orderByDesc('is_current')->orderByDesc('start_date')->get();
        $sessionId = $request->integer('academic_session_id') ?: optional($sessions->firstWhere('is_current', true))->id ?: optional($sessions->first())->id;

        $structures = FeeStructure::with(['schoolClass', 'session'])
            ->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId))
            ->get()
            ->sortBy(fn ($s) => [$s->schoolClass?->name, $s->title])
            ->values();

        // How many installments already generated per structure
        $generated = Fee::whereNotNull('fee_structure_id')
            ->selectRaw('fee_structure_id, COUNT(*) as c')
            ->groupBy('fee_structure_id')->pluck('c', 'fee_structure_id');

        return view('fees.structures.index', compact('structures', 'sessions', 'sessionId', 'generated'));
    }

    public function create(Request $request)
    {
        $this->can($request, 'fees.create');

        return view('fees.structures.create', [
            'sessions' => AcademicSession::orderByDesc('is_current')->orderByDesc('start_date')->get(),
            'classes' => SchoolClass::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->can($request, 'fees.create');

        $schoolId = Tenant::getSchoolId();
        $validated = $request->validate([
            'academic_session_id' => ['required', Rule::exists('academic_sessions', 'id')->where('school_id', $schoolId)],
            'school_class_id' => ['required', Rule::exists('school_classes', 'id')->where('school_id', $schoolId)],
            'title' => [
                'required', 'string', 'max:255',
                Rule::unique('fee_structures')->where(fn ($q) => $q
                    ->where('academic_session_id', $request->academic_session_id)
                    ->where('school_class_id', $request->school_class_id)),
            ],
            'amount' => 'required|numeric|min:0',
            'frequency' => ['required', Rule::in(array_keys(FeeStructure::FREQUENCIES))],
        ], [
            'title.unique' => 'This class already has a fee with that title for the selected session.',
        ]);

        FeeStructure::create($validated + ['school_id' => $schoolId]);

        return redirect()->route('fees.structures.index', ['academic_session_id' => $validated['academic_session_id']])
            ->with('success', 'Fee added. Use “Generate” to create student installments.');
    }

    public function destroy(Request $request, FeeStructure $structure)
    {
        $this->can($request, 'fees.create');
        abort_if($structure->school_id !== Tenant::getSchoolId(), 404);

        // Remove only its unpaid installments; keep ones with payments for the record
        $structure->fees()->where('paid_amount', 0)->delete();
        $structure->delete();

        return back()->with('success', 'Fee removed (paid installments kept).');
    }

    /** Create installments for every active student of the structure's class/session. */
    public function generate(Request $request, FeeStructure $structure)
    {
        $this->can($request, 'fees.create');
        abort_if($structure->school_id !== Tenant::getSchoolId(), 404);

        $schoolId = Tenant::getSchoolId();
        $periods = $structure->periods();
        $amount = $structure->amountPerPeriod();

        $enrollments = Enrollment::where('academic_session_id', $structure->academic_session_id)
            ->where('school_class_id', $structure->school_class_id)
            ->where('status', 'active')
            ->with('student')
            ->get()
            ->filter(fn ($e) => $e->student);

        if ($enrollments->isEmpty()) {
            return back()->with('error', 'No active students enrolled in this class for the session.');
        }

        $created = 0;

        DB::transaction(function () use ($enrollments, $periods, $amount, $structure, $schoolId, $request, &$created) {
            foreach ($enrollments as $enrollment) {
                foreach ($periods as $p) {
                    $fee = Fee::firstOrNew([
                        'fee_structure_id' => $structure->id,
                        'student_id' => $enrollment->student_id,
                        'period_no' => $p['no'],
                    ]);

                    if ($fee->exists) {
                        continue; // already generated — don't disturb payments
                    }

                    $fee->fill([
                        'school_id' => $schoolId,
                        'academic_session_id' => $structure->academic_session_id,
                        'school_class_id' => $structure->school_class_id,
                        'title' => $structure->title,
                        'period' => $p['label'],
                        'amount' => $amount,
                        'paid_amount' => 0,
                        'due_date' => $p['due'],
                        'status' => 'unpaid',
                        'created_by' => $request->user()->id,
                    ])->save();
                    $created++;
                }
            }
        });

        $msg = $created > 0
            ? "{$created} installment(s) generated for {$enrollments->count()} student(s)."
            : 'All installments were already generated.';

        return back()->with('success', $msg);
    }
}
