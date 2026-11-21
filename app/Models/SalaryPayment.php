<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryPayment extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id', 'user_id', 'employee_name', 'designation', 'month',
        'basic', 'allowances', 'deductions', 'net_salary',
        'status', 'paid_on', 'method', 'remarks', 'created_by',
    ];

    protected $casts = [
        'basic' => 'decimal:2',
        'allowances' => 'decimal:2',
        'deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'paid_on' => 'date',
    ];

    public function getMonthLabelAttribute(): string
    {
        return \Illuminate\Support\Carbon::createFromFormat('Y-m', $this->month)->format('F Y');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
