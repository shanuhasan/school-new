<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToSchool;

class Subject extends Model
{
    use HasFactory, SoftDeletes, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'name',
        'code',
    ];

    public function classes()
    {
        return $this->belongsToMany(SchoolClass::class, 'class_subjects')
                    ->withPivot('type', 'id')
                    ->withTimestamps();
    }
}
