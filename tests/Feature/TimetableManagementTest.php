<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\School;
use App\Models\AcademicSession;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Timetable;
use Illuminate\Support\Facades\DB;

class TimetableManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $school;
    protected $session;
    protected $schoolClass;
    protected $section;
    protected $subject;
    protected $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $this->school = School::create([
            'name' => 'Test School',
            'slug' => 'test-school',
            'email' => 'test@school.com',
            'phone' => '1234567890',
            'address' => 'Test Address',
            'status' => 'active',
            'code' => 'TS01'
        ]);

        $this->admin = User::factory()->create([
            'current_school_id' => $this->school->id,
            'email' => 'admin@test.com'
        ]);
        $this->admin->assignRole('Super Admin');
        $this->admin->schools()->attach($this->school->id);

        $this->session = AcademicSession::create([
            'school_id' => $this->school->id,
            'name' => '2025-2026',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'is_current' => true
        ]);

        $this->schoolClass = SchoolClass::create([
            'school_id' => $this->school->id,
            'name' => 'Class 1',
            'numeric_value' => 1
        ]);

        $this->section = Section::create([
            'school_class_id' => $this->schoolClass->id,
            'name' => 'A'
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Mathematics',
            'code' => 'MATH1'
        ]);

        $this->teacher = User::factory()->create([
            'current_school_id' => $this->school->id,
            'email' => 'teacher@test.com'
        ]);
        $this->teacher->assignRole('Teacher');
        $this->teacher->schools()->attach($this->school->id);
    }

    public function test_can_view_timetable_manage_page()
    {
        $response = $this->actingAs($this->admin)->get(route('academic.timetables.manage', [
            'school_class_id' => $this->schoolClass->id,
            'section_id' => $this->section->id
        ]));

        $response->assertStatus(200);
        $response->assertSee('Class Timetable');
    }

    public function test_can_add_timetable_period()
    {
        $response = $this->actingAs($this->admin)->post(route('academic.timetables.store'), [
            'school_class_id' => $this->schoolClass->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '08:45',
            'room_number' => '101'
        ]);

        $response->assertRedirect(route('academic.timetables.manage', [
            'school_class_id' => $this->schoolClass->id,
            'section_id' => $this->section->id
        ]));

        $this->assertDatabaseHas('timetables', [
            'school_class_id' => $this->schoolClass->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'room_number' => '101'
        ]);
    }

    public function test_prevents_teacher_conflict()
    {
        // Add first period
        Timetable::create([
            'school_id' => $this->school->id,
            'academic_session_id' => $this->session->id,
            'school_class_id' => $this->schoolClass->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '08:45'
        ]);

        // Attempt to schedule the same teacher in another class at the same time
        $class2 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 2', 'numeric_value' => 2]);
        $section2 = Section::create(['school_class_id' => $class2->id, 'name' => 'A']);

        $response = $this->actingAs($this->admin)->post(route('academic.timetables.store'), [
            'school_class_id' => $class2->id,
            'section_id' => $section2->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id, // Same teacher
            'day_of_week' => 1,
            'start_time' => '08:15', // Overlapping time
            'end_time' => '09:00'
        ]);

        $response->assertSessionHasErrors('teacher_id');
        $this->assertEquals(1, Timetable::count());
    }

    public function test_prevents_class_conflict()
    {
        // Add first period
        Timetable::create([
            'school_id' => $this->school->id,
            'academic_session_id' => $this->session->id,
            'school_class_id' => $this->schoolClass->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '08:45'
        ]);

        $teacher2 = User::factory()->create(['current_school_id' => $this->school->id]);
        $teacher2->assignRole('Teacher');
        $teacher2->schools()->attach($this->school->id);

        // Attempt to schedule the same class with another subject/teacher at the same time
        $response = $this->actingAs($this->admin)->post(route('academic.timetables.store'), [
            'school_class_id' => $this->schoolClass->id, // Same class
            'section_id' => $this->section->id, // Same section
            'subject_id' => $this->subject->id,
            'teacher_id' => $teacher2->id, // Different teacher
            'day_of_week' => 1,
            'start_time' => '08:15', // Overlapping time
            'end_time' => '09:00'
        ]);

        $response->assertSessionHasErrors('start_time');
        $this->assertEquals(1, Timetable::count());
    }

    public function test_teacher_can_view_own_timetable()
    {
        // Add a period for this teacher
        Timetable::create([
            'school_id' => $this->school->id,
            'academic_session_id' => $this->session->id,
            'school_class_id' => $this->schoolClass->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '08:45'
        ]);

        $response = $this->actingAs($this->teacher)->get(route('staff.timetables.teacher'));
        $response->assertStatus(200);
        $response->assertSee('08:00 AM - 08:45 AM');
    }
}
