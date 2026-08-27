<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Traits\BelongsToSchool;

class Attendance extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'academic_session_id',
        'student_id',
        'school_class_id',
        'section_id',
        'date',
        'status',
        'remarks',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}
