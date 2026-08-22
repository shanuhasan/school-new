<?php

namespace App\Models\Traits;

use App\Facades\Tenant;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToSchool
{
    /**
     * Boot the trait to add a global scope for the current school.
     */
    protected static function bootBelongsToSchool()
    {
        static::addGlobalScope('school', function (Builder $builder) {
            $schoolId = Tenant::getSchoolId();
            if ($schoolId) {
                $builder->where('school_id', $schoolId);
            }
        });
    }

    /**
     * Scope a query to only include records of the current school.
     */
    public function scopeForCurrentSchool(Builder $query): Builder
    {
        $schoolId = Tenant::getSchoolId();

        if (!$schoolId) {
            // If no school context is set, we return no records as a security measure,
            // unless we intentionally want global access (which we don't by default).
            return $query->where('school_id', -1);
        }

        return $query->where('school_id', $schoolId);
    }

    /**
     * Scope a query to only include records of a specific school.
     */
    public function scopeForSchool(Builder $query, $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    /**
     * Relationship to School
     */
    public function school()
    {
        return $this->belongsTo(\App\Models\School::class);
    }
}
