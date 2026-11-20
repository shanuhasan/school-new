<?php

namespace Tests\Feature;

use App\Facades\Tenant;
use App\Models\AcademicSession;
use App\Models\Guardian;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPanelTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->school = School::factory()->create();
        Tenant::setSchool($this->school);
    }

    private function user(string $role): User
    {
        $u = User::factory()->create(['current_school_id' => $this->school->id]);
        $u->assignRole($role);
        $u->schools()->attach($this->school->id);

        return $u;
    }

    public function test_admin_sees_school_wide_dashboard_and_full_menu()
    {
        $admin = $this->user('School Admin');

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Active students')       // admin stat grid
            ->assertSee('Staff &amp; Teachers', false)   // menu
            ->assertSee('Academic setup')
            ->assertSee('Reports');
    }

    public function test_teacher_sees_teacher_panel_and_limited_menu()
    {
        $teacher = $this->user('Teacher');
        Teacher::factory()->create(['school_id' => $this->school->id, 'user_id' => $teacher->id]);

        $response = $this->actingAs($teacher)->get(route('dashboard'))->assertOk();
        $response->assertSee('My classes');                 // teacher panel
        $response->assertSee('Attendance');                 // allowed
        $response->assertDontSee('Academic setup');         // not allowed
        $response->assertDontSee('Staff &amp; Teachers', false);
    }

    public function test_student_sees_student_panel_and_minimal_menu()
    {
        $student = $this->user('Student');
        Student::create(['school_id' => $this->school->id, 'user_id' => $student->id, 'admission_number' => 'S-1',
            'first_name' => 'Riya', 'last_name' => 'Rao', 'date_of_birth' => '2015-01-01', 'gender' => 'female',
            'admission_date' => '2026-04-01', 'status' => 'active']);

        $response = $this->actingAs($student)->get(route('dashboard'))->assertOk();
        $response->assertSee('My attendance');              // student panel
        $response->assertSee('Homework');                   // allowed in menu
        $response->assertDontSee('Academic setup');
        $response->assertDontSee('Attendance</span>', false); // no attendance menu item (can't take attendance)
    }

    public function test_parent_sees_children_panel()
    {
        $parentUser = $this->user('Parent');
        $guardian = Guardian::create(['school_id' => $this->school->id, 'user_id' => $parentUser->id, 'first_name' => 'Asha', 'last_name' => 'Rao', 'phone' => '9']);
        $child = Student::create(['school_id' => $this->school->id, 'admission_number' => 'S-2', 'first_name' => 'Kabir', 'last_name' => 'Rao',
            'date_of_birth' => '2015-01-01', 'gender' => 'male', 'admission_date' => '2026-04-01', 'status' => 'active']);
        $guardian->students()->attach($child->id, ['relationship' => 'Father']);

        $this->actingAs($parentUser)->get(route('dashboard'))
            ->assertOk()->assertSee('My children')->assertSee('Kabir Rao');
    }

    public function test_role_with_no_permissions_sees_generic_panel()
    {
        $librarian = $this->user('Librarian');

        $this->actingAs($librarian)->get(route('dashboard'))
            ->assertOk()
            ->assertSee("doesn't have any modules enabled", false)
            ->assertDontSee('Academic setup');
    }

    public function test_granting_a_permission_reveals_the_menu_item()
    {
        $librarian = $this->user('Librarian');

        // No students menu before
        $this->actingAs($librarian)->get(route('dashboard'))->assertDontSee('>Students<', false);

        // Grant via the manager (as Super Admin)
        $admin = $this->user('Super Admin');
        $this->actingAs($admin)->put(route('admin.roles.update', \Spatie\Permission\Models\Role::findByName('Librarian')), [
            'permissions' => ['students.create'],
        ]);

        // Now the Students menu appears for the librarian
        $this->actingAs($librarian->fresh())->get(route('dashboard'))->assertSee('All students');
    }
}
