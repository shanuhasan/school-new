<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Staff;
use App\Models\User;
use App\Models\Department;
use App\Models\Designation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use App\Facades\Tenant;

class StaffManagementTest extends TestCase
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

    public function test_can_view_staff_list()
    {
        Staff::factory()->create(['school_id' => $this->school->id]);

        $response = $this->actingAs($this->superAdmin)->get(route('staff.staffs.index'));

        $response->assertStatus(200);
    }

    public function test_can_create_staff()
    {
        $department = Department::factory()->create(['school_id' => $this->school->id]);
        $designation = Designation::factory()->create(['school_id' => $this->school->id]);
        Role::firstOrCreate(['name' => 'Staff']);

        $response = $this->actingAs($this->superAdmin)->post(route('staff.staffs.store'), [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'staff@example.com',
            'phone' => '1234567890',
            'employee_id' => 'STF001',
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('staff.staffs.index'));
        $this->assertDatabaseHas('users', ['email' => 'staff@example.com']);
        $this->assertDatabaseHas('staff', ['employee_id' => 'STF001']);
    }
}
