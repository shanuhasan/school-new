<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDocument extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'student_id',
        'title',
        'file_path',
        'disk',
        'mime_type',
        'file_size',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
