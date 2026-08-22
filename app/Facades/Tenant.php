<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;
use App\Services\TenantManager;

/**
 * @method static void setSchool(\App\Models\School $school)
 * @method static \App\Models\School|null getSchool()
 * @method static int|null getSchoolId()
 * @method static bool hasSchool()
 *
 * @see \App\Services\TenantManager
 */
class Tenant extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TenantManager::class;
    }
}
