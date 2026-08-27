<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToSchool;

class Mark extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id', 'exam_subject_id', 'student_id',
        'marks_obtained', 'is_absent', 'remarks'
    ];

    protected $casts = [
        'marks_obtained' => 'decimal:2',
        'is_absent' => 'boolean',
    ];

    public function examSubject()
    {
        return $this->belongsTo(ExamSubject::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
