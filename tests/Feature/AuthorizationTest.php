<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Database\Seeders\RolesAndPermissionsSeeder;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed the roles and permissions
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_user_can_be_assigned_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Teacher');

        $this->assertTrue($user->hasRole('Teacher'));
        $this->assertFalse($user->hasRole('Student'));
    }

    public function test_teacher_has_correct_permissions(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Teacher');

        $this->assertTrue($user->can('students.view'));
        $this->assertTrue($user->can('exams.view'));
        $this->assertFalse($user->can('fees.collect'));
    }

    public function test_super_admin_bypasses_all_permissions(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        // Even if Super Admin isn't explicitly granted a random permission, they should have it via Gate::before
        $this->assertTrue($user->can('students.view'));
        $this->assertTrue($user->can('some.random.permission'));
    }

    public function test_unauthorized_user_cannot_perform_action(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Student');

        // Students shouldn't be able to enter marks
        $this->assertFalse($user->can('marks.enter'));
    }
}
