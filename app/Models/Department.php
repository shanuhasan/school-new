<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'name',
        'description',
    ];

    public function teachers()
    {
        return $this->hasMany(Teacher::class);
    }

    public function staffs()
    {
        return $this->hasMany(Staff::class);
    }
}
