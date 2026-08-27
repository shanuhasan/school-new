<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToSchool;

class ExamType extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = ['school_id', 'name'];

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }
}
