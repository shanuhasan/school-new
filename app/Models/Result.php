<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToSchool;

class Result extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id', 'academic_session_id', 'exam_id', 'school_class_id',
        'section_id', 'student_id', 'total_max_marks', 'total_obtained_marks',
        'percentage', 'grade_id', 'status', 'is_published', 'published_at'
    ];

    protected $casts = [
        'total_max_marks' => 'decimal:2',
        'total_obtained_marks' => 'decimal:2',
        'percentage' => 'decimal:2',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }
}
