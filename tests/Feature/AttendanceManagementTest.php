<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\User;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\AcademicSession;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\Attendance;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Facades\Tenant;

class AttendanceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $school;
    protected $admin;
    protected $session;
    protected $schoolClass;
    protected $section;
    protected $student;
    protected $enrollment;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->school = School::factory()->create();
        Tenant::setSchool($this->school);

        // Permissions
        $role = Role::firstOrCreate(['name' => 'Super Admin']);
        Permission::firstOrCreate(['name' => 'attendance.manage']);
        Permission::firstOrCreate(['name' => 'attendance.view_reports']);
        Permission::firstOrCreate(['name' => 'attendance.create']);
        Permission::firstOrCreate(['name' => 'attendance.update']);
        $role->givePermissionTo(Permission::all());

        $this->admin = User::factory()->create(['current_school_id' => $this->school->id]);
        $this->admin->schools()->attach($this->school->id);
        $this->admin->assignRole('Super Admin');

        $this->session = AcademicSession::create([
            'school_id' => $this->school->id,
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_current' => true,
        ]);

        $this->schoolClass = SchoolClass::create([
            'school_id' => $this->school->id,
            'name' => 'Class 1'
        ]);

        $this->section = Section::create([
            'school_id' => $this->school->id,
            'school_class_id' => $this->schoolClass->id,
            'name' => 'A'
        ]);

        $this->student = Student::create([
            'school_id' => $this->school->id,
            'admission_number' => 'ADM001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => '2010-01-01',
            'gender' => 'male',
            'admission_date' => '2026-04-01',
        ]);

        $this->enrollment = Enrollment::create([
            'school_id' => $this->school->id,
            'academic_session_id' => $this->session->id,
            'student_id' => $this->student->id,
            'school_class_id' => $this->schoolClass->id,
            'section_id' => $this->section->id,
        ]);
    }

    public function test_can_view_attendance_mark_page()
    {
        $response = $this->actingAs($this->admin)->get(route('academic.attendance.mark', [
            'school_class_id' => $this->schoolClass->id,
            'section_id' => $this->section->id,
            'date' => date('Y-m-d')
        ]));

        $response->assertStatus(200);
        $response->assertSee($this->student->first_name);
    }

    public function test_can_store_attendance()
    {
        $date = date('Y-m-d');
        
        $response = $this->actingAs($this->admin)->post(route('academic.attendance.store'), [
            'school_class_id' => $this->schoolClass->id,
            'section_id' => $this->section->id,
            'date' => $date,
            'attendance' => [
                $this->student->id => [
                    'status' => 'present',
                    'remarks' => 'Good'
                ]
            ]
        ]);

        $response->assertRedirect(route('academic.attendance.index'));
        
        $this->assertDatabaseHas('attendances', [
            'student_id' => $this->student->id,
            'date' => $date,
            'status' => 'present'
        ]);
    }

    public function test_duplicate_attendance_updates_existing_record()
    {
        $date = date('Y-m-d');
        
        // First request
        $this->actingAs($this->admin)->post(route('academic.attendance.store'), [
            'school_class_id' => $this->schoolClass->id,
            'section_id' => $this->section->id,
            'date' => $date,
            'attendance' => [
                $this->student->id => [
                    'status' => 'present',
                ]
            ]
        ]);

        // Second request, updating to absent
        $this->actingAs($this->admin)->post(route('academic.attendance.store'), [
            'school_class_id' => $this->schoolClass->id,
            'section_id' => $this->section->id,
            'date' => $date,
            'attendance' => [
                $this->student->id => [
                    'status' => 'absent',
                ]
            ]
        ]);
        
        $count = Attendance::where('student_id', $this->student->id)->where('date', $date)->count();
        $this->assertEquals(1, $count); // Only one record exists

        $this->assertDatabaseHas('attendances', [
            'student_id' => $this->student->id,
            'date' => $date,
            'status' => 'absent'
        ]);
    }
}
