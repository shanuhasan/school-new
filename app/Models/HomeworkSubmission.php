<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Traits\BelongsToSchool;

class HomeworkSubmission extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'homework_id',
        'student_id',
        'submission_text',
        'attachment_path',
        'status',
        'marks',
        'teacher_feedback',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'marks' => 'decimal:2',
    ];

    public function homework()
    {
        return $this->belongsTo(Homework::class, 'homework_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
