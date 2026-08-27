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
use App\Models\ExamType;
use App\Models\Exam;
use App\Models\ExamSubject;
use App\Models\Grade;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ExaminationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $school;
    protected $session;
    protected $class;
    protected $section;
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->school = School::create([
            'name' => 'Test Exam School',
            'email' => 'exam@test.com',
            'phone' => '1234567890',
            'address' => 'Test Address',
            'status' => 'active',
            'domain' => 'testexam.com',
            'code' => 'TEST',
            'slug' => 'test-exam-school'
        ]);

        $this->admin = User::factory()->create([
            'current_school_id' => $this->school->id
        ]);
        $this->admin->assignRole('School Admin');
        $this->admin->schools()->attach($this->school->id);

        $this->session = AcademicSession::create([
            'school_id' => $this->school->id,
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_current' => true
        ]);

        $this->class = SchoolClass::create([
            'school_id' => $this->school->id,
            'name' => 'Grade 10',
            'level' => 10
        ]);

        $this->section = Section::create([
            'school_id' => $this->school->id,
            'school_class_id' => $this->class->id,
            'name' => 'A'
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Mathematics',
            'code' => 'MATH101',
            'type' => 'theory'
        ]);
    }

    public function test_admin_can_manage_exam_types_and_exams()
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['tenant_school_id' => $this->school->id])
            ->post('/academic/exam-types', [
                'name' => 'Midterm Exam'
            ]);
            
        $response->assertStatus(302);
        $this->assertDatabaseHas('exam_types', ['name' => 'Midterm Exam']);
        
        $examType = ExamType::first();
        
        $response = $this->actingAs($this->admin)
            ->withSession(['tenant_school_id' => $this->school->id])
            ->post('/academic/exams', [
                'name' => 'Midterm 2026',
                'exam_type_id' => $examType->id,
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-10'
            ]);
            
        $response->assertStatus(302);
        $this->assertDatabaseHas('exams', ['name' => 'Midterm 2026']);
    }

    public function test_admin_can_schedule_subject_and_enter_marks()
    {
        $examType = ExamType::create(['school_id' => $this->school->id, 'name' => 'Test Type']);
        $exam = Exam::create([
            'school_id' => $this->school->id,
            'academic_session_id' => $this->session->id,
            'exam_type_id' => $examType->id,
            'name' => 'Test Exam',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-05'
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession(['tenant_school_id' => $this->school->id])
            ->post("/academic/exams/{$exam->id}/subjects", [
                'school_class_id' => $this->class->id,
                'section_id' => $this->section->id,
                'subject_id' => $this->subject->id,
                'exam_date' => '2026-10-02',
                'max_marks' => 100,
                'passing_marks' => 40
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('exam_subjects', ['max_marks' => 100]);
        
        $examSubject = ExamSubject::first();
        
        // Create student user
        $studentUser = User::factory()->create(['current_school_id' => $this->school->id]);
        $studentUser->assignRole('Student');
        $studentUser->schools()->attach($this->school->id);

        // Create student profile
        $studentProfile = \App\Models\Student::create([
            'school_id' => $this->school->id,
            'user_id' => $studentUser->id,
            'admission_number' => 'ADM101',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => '2010-01-01',
            'gender' => 'male',
            'admission_date' => '2026-04-01'
        ]);
        
        // Enroll student
        \App\Models\Enrollment::create([
            'school_id' => $this->school->id,
            'academic_session_id' => $this->session->id,
            'student_id' => $studentProfile->id,
            'school_class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'roll_number' => '101'
        ]);

        // Enter Marks
        $response = $this->actingAs($this->admin)
            ->withSession(['tenant_school_id' => $this->school->id])
            ->post('/academic/marks', [
                'exam_subject_id' => $examSubject->id,
                'marks' => [
                    $studentUser->id => [
                        'obtained' => 85,
                        'is_absent' => false,
                        'remarks' => 'Good'
                    ]
                ]
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('marks', [
            'student_id' => $studentUser->id,
            'marks_obtained' => 85
        ]);
    }
}
