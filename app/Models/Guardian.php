<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Guardian extends Model
{
    use HasFactory, BelongsToSchool, \Illuminate\Database\Eloquent\Concerns\HasUuids;

    /** Only "uuid" is a generated GUID; "id" stays the auto-increment primary key. */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /** URLs use the GUID: /guardians/{uuid}, /guardians/{uuid}/edit, ... */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected $fillable = [
        'school_id',
        'first_name',
        'last_name',
        'phone',
        'email',
        'occupation',
        'address',
    ];

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class)
            ->withPivot('relationship', 'is_emergency_contact')
            ->withTimestamps();
    }

    /** Search shared by the guardians page and the Excel export. */
    public function scopeFilter(\Illuminate\Database\Eloquent\Builder $query, array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
