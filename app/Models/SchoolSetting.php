<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SchoolSetting extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'key',
        'value',
    ];
}
