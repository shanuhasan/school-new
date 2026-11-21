<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fee extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id', 'student_id', 'academic_session_id', 'title',
        'amount', 'discount', 'fine', 'paid_amount',
        'due_date', 'paid_on', 'method', 'status', 'remarks', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'fine' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_on' => 'date',
    ];

    /** Total the student owes: base + fine − discount. */
    public function getPayableAttribute(): float
    {
        return round((float) $this->amount + (float) $this->fine - (float) $this->discount, 2);
    }

    public function getBalanceAttribute(): float
    {
        return round($this->payable - (float) $this->paid_amount, 2);
    }

    /** Recompute status from what's been paid vs what's payable. */
    public function syncStatus(): void
    {
        $this->status = match (true) {
            $this->paid_amount <= 0 => 'unpaid',
            $this->balance > 0 => 'partial',
            default => 'paid',
        };
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function session()
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }
}
