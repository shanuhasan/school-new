<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Department;
use App\Models\Designation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use App\Facades\Tenant;

class TeacherManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdmin;
    protected $school;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->school = School::factory()->create();
        Tenant::setSchool($this->school);

        $role = Role::firstOrCreate(['name' => 'Super Admin']);
        Permission::firstOrCreate(['name' => 'staff.manage']);
        
        $this->superAdmin = User::factory()->create([
            'current_school_id' => $this->school->id
        ]);
        $this->superAdmin->assignRole($role);
        $this->superAdmin->schools()->attach($this->school);
    }

    public function test_can_view_teachers_list()
    {
        Teacher::factory()->create(['school_id' => $this->school->id]);

        $response = $this->actingAs($this->superAdmin)->get(route('staff.teachers.index'));

        $response->assertStatus(200);
    }

    public function test_can_create_teacher()
    {
        $department = Department::factory()->create(['school_id' => $this->school->id]);
        $designation = Designation::factory()->create(['school_id' => $this->school->id]);
        Role::firstOrCreate(['name' => 'Teacher']);

        $response = $this->actingAs($this->superAdmin)->post(route('staff.teachers.store'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'teacher@example.com',
            'phone' => '1234567890',
            'employee_id' => 'EMP001',
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('staff.teachers.index'));
        $this->assertDatabaseHas('users', ['email' => 'teacher@example.com']);
        $this->assertDatabaseHas('teachers', ['employee_id' => 'EMP001']);
    }
}
