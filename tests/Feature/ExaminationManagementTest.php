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
                    $studentProfile->id => [
                        'obtained' => 85,
                        'is_absent' => false,
                        'remarks' => 'Good'
                    ]
                ]
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('marks', [
            'student_id' => $studentProfile->id,
            'marks_obtained' => 85
        ]);
    }

    public function test_marks_entry_shows_section_specific_subjects_with_and_without_section()
    {
        $examType = ExamType::create(['school_id' => $this->school->id, 'name' => 'Half Yearly']);
        $exam = Exam::create([
            'school_id' => $this->school->id, 'academic_session_id' => $this->session->id, 'exam_type_id' => $examType->id,
            'name' => 'Half Yearly', 'start_date' => '2026-10-01', 'end_date' => '2026-10-05',
        ]);
        // Subject scheduled for a SPECIFIC section (the bug: it never showed unless the section was also picked)
        ExamSubject::create([
            'exam_id' => $exam->id, 'school_class_id' => $this->class->id, 'section_id' => $this->section->id,
            'subject_id' => $this->subject->id, 'exam_date' => '2026-10-02', 'max_marks' => 100, 'passing_marks' => 40,
        ]);

        // Exam + class only (no section) must still list the subject
        $this->actingAs($this->admin)
            ->get(route('academic.marks.index', ['exam_id' => $exam->id, 'school_class_id' => $this->class->id]))
            ->assertOk()
            ->assertSee('Mathematics');

        // With the matching section too
        $this->actingAs($this->admin)
            ->get(route('academic.marks.index', ['exam_id' => $exam->id, 'school_class_id' => $this->class->id, 'section_id' => $this->section->id]))
            ->assertOk()
            ->assertSee('Mathematics');
    }

    public function test_marks_entry_requires_permission()
    {
        $outsider = User::factory()->create(['current_school_id' => $this->school->id]);
        $outsider->assignRole('Librarian'); // no marks.enter
        $outsider->schools()->attach($this->school->id);

        $this->actingAs($outsider)->get(route('academic.marks.index'))->assertForbidden();
    }

    public function test_marks_entry_lists_subjects_by_exam_without_a_class()
    {
        $examType = ExamType::create(['school_id' => $this->school->id, 'name' => 'Half Yearly']);
        $exam = Exam::create([
            'school_id' => $this->school->id, 'academic_session_id' => $this->session->id, 'exam_type_id' => $examType->id,
            'name' => 'Half Yearly', 'start_date' => '2026-10-01', 'end_date' => '2026-10-05',
        ]);
        ExamSubject::create([
            'exam_id' => $exam->id, 'school_class_id' => $this->class->id, 'section_id' => $this->section->id,
            'subject_id' => $this->subject->id, 'exam_date' => '2026-10-02', 'max_marks' => 100, 'passing_marks' => 40,
        ]);

        // Exam only — subjects show without choosing a class
        $this->actingAs($this->admin)
            ->get(route('academic.marks.index', ['exam_id' => $exam->id]))
            ->assertOk()->assertSee('Mathematics')->assertSee('1 subject to mark');
    }

    public function test_section_filter_narrows_class_wide_roster_and_all_shows_everyone()
    {
        $sectionB = Section::create(['school_id' => $this->school->id, 'school_class_id' => $this->class->id, 'name' => 'B']);
        foreach ([[$this->section, 'Aisha', 'Khan', 'ADM-A'], [$sectionB, 'Bilal', 'Sheikh', 'ADM-B']] as [$sec, $first, $last, $adm]) {
            $student = \App\Models\Student::create([
                'school_id' => $this->school->id, 'admission_number' => $adm, 'first_name' => $first, 'last_name' => $last,
                'date_of_birth' => '2012-01-01', 'gender' => 'male', 'admission_date' => '2026-04-01', 'status' => 'active',
            ]);
            \App\Models\Enrollment::create([
                'school_id' => $this->school->id, 'academic_session_id' => $this->session->id, 'student_id' => $student->id,
                'school_class_id' => $this->class->id, 'section_id' => $sec->id, 'roll_number' => '1', 'status' => 'active',
            ]);
        }

        $examType = ExamType::create(['school_id' => $this->school->id, 'name' => 'Annual']);
        $exam = Exam::create([
            'school_id' => $this->school->id, 'academic_session_id' => $this->session->id, 'exam_type_id' => $examType->id,
            'name' => 'Annual', 'start_date' => '2026-12-01', 'end_date' => '2026-12-05',
        ]);
        // Class-wide subject (no section)
        $classWide = ExamSubject::create([
            'exam_id' => $exam->id, 'school_class_id' => $this->class->id, 'section_id' => null,
            'subject_id' => $this->subject->id, 'exam_date' => '2026-12-02', 'max_marks' => 50, 'passing_marks' => 20,
        ]);

        // Section = All → both students, with a Section column
        $this->actingAs($this->admin)
            ->get(route('academic.marks.index', ['exam_id' => $exam->id, 'exam_subject_id' => $classWide->id]))
            ->assertOk()->assertSee('all sections')->assertSee('Aisha Khan')->assertSee('Bilal Sheikh');

        // Section = A → only Aisha
        $this->actingAs($this->admin)
            ->get(route('academic.marks.index', ['exam_id' => $exam->id, 'school_class_id' => $this->class->id, 'section_id' => $this->section->id, 'exam_subject_id' => $classWide->id]))
            ->assertOk()->assertSee('Aisha Khan')->assertDontSee('Bilal Sheikh');
    }

    public function test_result_processing_allows_super_admin_and_staff_but_blocks_others()
    {
        $examType = ExamType::create(['school_id' => $this->school->id, 'name' => 'Final']);
        $exam = Exam::create([
            'school_id' => $this->school->id, 'academic_session_id' => $this->session->id, 'exam_type_id' => $examType->id,
            'name' => 'Final', 'start_date' => '2026-11-01', 'end_date' => '2026-11-05',
        ]);
        $payload = ['exam_id' => $exam->id, 'school_class_id' => $this->class->id, 'section_id' => $this->section->id];

        // Super Admin: has NO explicit permissions, passes via Gate::before (this was the 403 bug)
        $super = User::factory()->create(['current_school_id' => $this->school->id]);
        $super->assignRole('Super Admin');
        $super->schools()->attach($this->school->id);
        $this->actingAs($super)->post(route('academic.results.process'), $payload)
            ->assertStatus(302)->assertSessionHasNoErrors();   // not 403

        // School Admin (all permissions) can process and reach the page
        $this->actingAs($this->admin)->post(route('academic.results.process'), $payload)->assertStatus(302);
        $this->actingAs($this->admin)->get(route('academic.results.index'))->assertOk();

        // Librarian (no permissions) is blocked from processing and from the page
        $lib = User::factory()->create(['current_school_id' => $this->school->id]);
        $lib->assignRole('Librarian');
        $lib->schools()->attach($this->school->id);
        $this->actingAs($lib)->post(route('academic.results.process'), $payload)->assertForbidden();
        $this->actingAs($lib)->get(route('academic.results.index'))->assertForbidden();
    }

    public function test_processing_all_sections_records_each_students_section_without_error()
    {
        $sectionB = Section::create(['school_id' => $this->school->id, 'school_class_id' => $this->class->id, 'name' => 'B']);
        $examType = ExamType::create(['school_id' => $this->school->id, 'name' => 'Final']);
        $exam = Exam::create([
            'school_id' => $this->school->id, 'academic_session_id' => $this->session->id, 'exam_type_id' => $examType->id,
            'name' => 'Final', 'start_date' => '2026-11-01', 'end_date' => '2026-11-05',
        ]);
        // Class-wide subject (no section)
        $examSubject = ExamSubject::create([
            'exam_id' => $exam->id, 'school_class_id' => $this->class->id, 'section_id' => null,
            'subject_id' => $this->subject->id, 'exam_date' => '2026-11-02', 'max_marks' => 100, 'passing_marks' => 40,
        ]);

        // One student per section, each with a mark entered
        $made = [];
        foreach ([[$this->section, 'Aisha', 'ADM-A', 80], [$sectionB, 'Bilal', 'ADM-B', 30]] as [$sec, $name, $adm, $score]) {
            $student = \App\Models\Student::create([
                'school_id' => $this->school->id, 'admission_number' => $adm, 'first_name' => $name, 'last_name' => 'X',
                'date_of_birth' => '2012-01-01', 'gender' => 'male', 'admission_date' => '2026-04-01', 'status' => 'active',
            ]);
            \App\Models\Enrollment::create([
                'school_id' => $this->school->id, 'academic_session_id' => $this->session->id, 'student_id' => $student->id,
                'school_class_id' => $this->class->id, 'section_id' => $sec->id, 'roll_number' => '1', 'status' => 'active',
            ]);
            \App\Models\Mark::create([
                'school_id' => $this->school->id, 'exam_subject_id' => $examSubject->id, 'student_id' => $student->id,
                'marks_obtained' => $score, 'is_absent' => false,
            ]);
            $made[$name] = ['student' => $student, 'section' => $sec];
        }

        // Process with NO section ("all sections") — this used to crash with a NOT NULL error
        $this->actingAs($this->admin)
            ->post(route('academic.results.process'), ['exam_id' => $exam->id, 'school_class_id' => $this->class->id])
            ->assertStatus(302)->assertSessionHas('success')->assertSessionHasNoErrors();

        // Each result stored with the student's own section, correct pass/fail
        $this->assertDatabaseHas('results', ['exam_id' => $exam->id, 'student_id' => $made['Aisha']['student']->id, 'section_id' => $this->section->id, 'status' => 'pass']);
        $this->assertDatabaseHas('results', ['exam_id' => $exam->id, 'student_id' => $made['Bilal']['student']->id, 'section_id' => $sectionB->id, 'status' => 'fail']);

        // Re-processing updates the same rows (no duplicate / unique violation)
        $this->actingAs($this->admin)
            ->post(route('academic.results.process'), ['exam_id' => $exam->id, 'school_class_id' => $this->class->id])
            ->assertStatus(302)->assertSessionHasNoErrors();
        $this->assertSame(2, \App\Models\Result::where('exam_id', $exam->id)->count());

        // Publish all sections locks them
        $this->actingAs($this->admin)
            ->post(route('academic.results.publish'), ['exam_id' => $exam->id, 'school_class_id' => $this->class->id])
            ->assertStatus(302)->assertSessionHas('success');
        $this->assertSame(2, \App\Models\Result::where('exam_id', $exam->id)->where('is_published', true)->count());
    }
}
