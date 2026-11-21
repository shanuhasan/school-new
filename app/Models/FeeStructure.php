<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class FeeStructure extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id', 'academic_session_id', 'school_class_id', 'title', 'amount', 'frequency',
    ];

    protected $casts = ['amount' => 'decimal:2'];

    public const FREQUENCIES = ['monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'yearly' => 'Yearly'];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function session()
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function fees()
    {
        return $this->hasMany(Fee::class);
    }

    /** How many periods this structure bills over the session. */
    public function periodCount(): int
    {
        return count($this->periods());
    }

    /** Amount charged each period (as entered — e.g. ₹3000 "quarterly" means ₹3000 per quarter). */
    public function amountPerPeriod(): float
    {
        return round((float) $this->amount, 2);
    }

    /**
     * Build the list of billing periods across the session.
     * @return array<int, array{no:int, label:string, due:Carbon}>
     */
    public function periods(): array
    {
        $session = $this->session;
        $start = $session?->start_date ? Carbon::parse($session->start_date)->startOfMonth() : now()->startOfMonth();
        $end = $session?->end_date ? Carbon::parse($session->end_date)->startOfMonth() : $start->copy()->addMonths(11);

        $months = [];
        for ($d = $start->copy(); $d->lte($end); $d->addMonth()) {
            $months[] = $d->copy();
        }
        if (empty($months)) {
            $months = [$start->copy()];
        }

        return match ($this->frequency) {
            'yearly' => [[
                'no' => 1,
                'label' => $session?->name ?? ('Annual ' . $start->year),
                'due' => $start->copy()->day(10),
            ]],
            'quarterly' => $this->chunkPeriods($months, 3, 'Q'),
            default => array_values(array_map(fn ($i, $m) => [
                'no' => $i + 1,
                'label' => $m->format('F Y'),
                'due' => $m->copy()->day(10),
            ], array_keys($months), $months)),
        };
    }

    private function chunkPeriods(array $months, int $size, string $prefix): array
    {
        $periods = [];
        foreach (array_chunk($months, $size) as $i => $chunk) {
            $first = $chunk[0];
            $last = end($chunk);
            $periods[] = [
                'no' => $i + 1,
                'label' => $prefix . ($i + 1) . ' (' . $first->format('M') . '–' . $last->format('M Y') . ')',
                'due' => $first->copy()->day(10),
            ];
        }

        return $periods;
    }
}
