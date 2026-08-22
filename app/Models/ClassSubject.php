<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ClassSubject extends Pivot
{
    protected $table = 'class_subjects';
    public $incrementing = true;

    protected $fillable = [
        'school_class_id',
        'subject_id',
        'type',
    ];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
