<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory, BelongsToSchool, \Illuminate\Database\Eloquent\Concerns\HasUuids;

    /** Only "uuid" is a generated GUID; "id" stays the auto-increment primary key. */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /** URLs use the GUID: /staff/teachers/{uuid}, /staff/teachers/{uuid}/edit, ... */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected $fillable = [
        'school_id',
        'user_id',
        'department_id',
        'designation_id',
        'employee_id',
        'joining_date',
        'qualification',
        'status',
    ];

    protected $casts = [
        'joining_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'teacher_subject')->withTimestamps();
    }

    public function classes()
    {
        return $this->belongsToMany(SchoolClass::class, 'teacher_school_class')->withTimestamps();
    }

    /**
     * Filters shared by the teachers page and the Excel export. Keys: search, department_id.
     * The search is grouped so it can never widen the school / department conditions.
     */
    public function scopeFilter(\Illuminate\Database\Eloquent\Builder $query, array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($u) use ($search) {
                    $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                })->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        return $query;
    }
}
