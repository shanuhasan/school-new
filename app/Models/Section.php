<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Section extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'school_class_id',
        'name',
    ];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class);
    }
}
