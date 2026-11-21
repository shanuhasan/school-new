<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fee extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id', 'student_id', 'academic_session_id', 'fee_structure_id', 'school_class_id',
        'title', 'period', 'period_no',
        'amount', 'discount', 'waived_amount', 'fine', 'paid_amount',
        'due_date', 'paid_on', 'method', 'status', 'remarks', 'waive_reason', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'waived_amount' => 'decimal:2',
        'fine' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_on' => 'date',
    ];

    /** Total the student owes: base + fine − discount − waiver. */
    public function getPayableAttribute(): float
    {
        return round((float) $this->amount + (float) $this->fine - (float) $this->discount - (float) $this->waived_amount, 2);
    }

    public function getBalanceAttribute(): float
    {
        return round($this->payable - (float) $this->paid_amount, 2);
    }

    /** Recompute status from what's been paid / waived vs what's payable. */
    public function syncStatus(): void
    {
        if ($this->balance > 0) {
            $this->status = ((float) $this->paid_amount > 0 || (float) $this->waived_amount > 0) ? 'partial' : 'unpaid';
        } else {
            // Nothing left to pay: "paid" if any money came in, otherwise it was fully waived
            $this->status = (float) $this->paid_amount > 0 ? 'paid' : ((float) $this->waived_amount > 0 ? 'waived' : 'unpaid');
        }
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function session()
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function structure()
    {
        return $this->belongsTo(FeeStructure::class, 'fee_structure_id');
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }
}
