<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\Fee;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Fee reports: total billed vs collected vs outstanding, grouped by month, quarter and year,
 * plus a per-student summary. Everything is scoped to the current school via the Fee global scope.
 */
class FeeReportController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('fees.view'), 403);

        // Years that actually have fees (by due date or payment date), for the year picker
        $years = Fee::query()->get(['due_date', 'paid_on', 'created_at'])
            ->flatMap(fn ($f) => [optional($f->due_date)->year, optional($f->paid_on)->year, $f->created_at?->year])
            ->filter()->unique()->sortDesc()->values();
        if ($years->isEmpty()) {
            $years = collect([now()->year]);
        }

        $year = (int) ($request->input('year') ?: $years->first());
        $sessionId = $request->integer('academic_session_id') ?: null;
        $status = $request->input('status');

        // Fees matching the session/status filter; bucketed in PHP (DB-agnostic)
        $fees = Fee::with('student:id,first_name,last_name,admission_number')
            ->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->get();

        // A fee belongs to the report year by its due date (fall back to created date)
        $forYear = $fees->filter(fn ($f) => ($f->due_date?->year ?? $f->created_at?->year) === $year);

        // ---- Monthly breakdown (1..12) ----
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $inMonth = $forYear->filter(fn ($f) => ($f->due_date?->month ?? $f->created_at?->month) === $m);
            $months[$m] = $this->totals($inMonth);
        }

        // ---- Quarterly subtotals ----
        $quarters = [];
        foreach ([1 => [1, 2, 3], 2 => [4, 5, 6], 3 => [7, 8, 9], 4 => [10, 11, 12]] as $q => $ms) {
            $quarters[$q] = $this->sumBuckets(array_map(fn ($m) => $months[$m], $ms));
        }

        $yearTotal = $this->totals($forYear);

        // ---- Collections received IN this year (by payment date), regardless of due date ----
        $collectedThisYear = (float) $fees->filter(fn ($f) => $f->paid_on?->year === $year)->sum('paid_amount');

        // ---- Per-student summary (for the selected year) ----
        $byStudent = $forYear->groupBy('student_id')->map(function ($group) {
            $t = $this->totals($group);
            $t['student'] = $group->first()->student;
            $t['count'] = $group->count();

            return (object) $t;
        })->sortByDesc('balance')->values();

        // ---- Per-year overview (all years) ----
        $byYear = $fees->groupBy(fn ($f) => $f->due_date?->year ?? $f->created_at?->year)
            ->map(fn ($g) => $this->totals($g))
            ->sortKeysDesc();

        return view('reports.fees', [
            'years' => $years,
            'year' => $year,
            'sessions' => AcademicSession::orderByDesc('is_current')->orderByDesc('start_date')->get(),
            'sessionId' => $sessionId,
            'status' => $status,
            'months' => $months,
            'quarters' => $quarters,
            'yearTotal' => $yearTotal,
            'collectedThisYear' => $collectedThisYear,
            'byStudent' => $byStudent,
            'byYear' => $byYear,
            'monthName' => fn ($m) => Carbon::create()->month($m)->format('M'),
        ]);
    }

    /** Billed (payable), collected (paid) and outstanding (balance) for a set of fees. */
    private function totals($fees): array
    {
        $billed = (float) $fees->sum(fn ($f) => $f->payable);
        $paid = (float) $fees->sum(fn ($f) => (float) $f->paid_amount);

        return ['billed' => $billed, 'paid' => $paid, 'balance' => round($billed - $paid, 2)];
    }

    private function sumBuckets(array $buckets): array
    {
        return [
            'billed' => array_sum(array_column($buckets, 'billed')),
            'paid' => array_sum(array_column($buckets, 'paid')),
            'balance' => round(array_sum(array_column($buckets, 'balance')), 2),
        ];
    }
}
