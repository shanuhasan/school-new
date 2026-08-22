<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'slug',
        'address',
        'email',
        'phone',
        'logo',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function settings()
    {
        return $this->hasMany(SchoolSetting::class);
    }

    public function academicSessions()
    {
        return $this->hasMany(AcademicSession::class);
    }
}
