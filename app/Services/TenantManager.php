<?php

namespace App\Services;

use App\Models\School;

class TenantManager
{
    protected ?School $school = null;

    /**
     * Set the current active school context.
     */
    public function setSchool(School $school): void
    {
        $this->school = $school;
    }

    /**
     * Get the current active school.
     */
    public function getSchool(): ?School
    {
        return $this->school;
    }

    /**
     * Get the current active school ID.
     */
    public function getSchoolId(): ?int
    {
        return $this->school?->id;
    }

    /**
     * Check if a school context is set.
     */
    public function hasSchool(): bool
    {
        return $this->school !== null;
    }
}
