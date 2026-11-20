<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory, BelongsToSchool, HasUuids;

    /**
     * Only the "uuid" column is a generated GUID; "id" stays the auto-increment primary key.
     * HasUuids fills it automatically when a student is created (form, import, seeders...).
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /** URLs use the GUID: /students/{uuid}, /students/{uuid}/edit, ... */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected $fillable = [
        'school_id',
        'admission_number',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'phone',
        'email',
        'address',
        'blood_group',
        'photo_path',
        'admission_date',
        'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'admission_date' => 'date',
    ];

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class)
            ->withPivot('relationship', 'is_emergency_contact')
            ->withTimestamps();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }

    /**
     * List filters shared by the students page and the Excel export.
     * Keys: search, class_id, section_id, session_id, status.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        $filters = array_filter($filters, fn ($v) => $v !== null && $v !== '');

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('admission_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (isset($filters['class_id'])) {
            $query->whereHas('enrollments', function ($q) use ($filters) {
                $q->where('school_class_id', $filters['class_id']);
                if (isset($filters['section_id'])) {
                    $q->where('section_id', $filters['section_id']);
                }
                if (isset($filters['session_id'])) {
                    $q->where('academic_session_id', $filters['session_id']);
                }
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }
}
