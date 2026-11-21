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
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class HomeworkManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        
        $this->school = School::factory()->create();
        $this->session = AcademicSession::factory()->create(['school_id' => $this->school->id, 'is_current' => true]);
        
        $this->teacher = User::factory()->create(['current_school_id' => $this->school->id]);
        $teacherRole = Role::firstOrCreate(['name' => 'Teacher']);
        $this->teacher->assignRole($teacherRole);
        $this->teacher->givePermissionTo(\Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'homework.manage']));
        $this->teacher->givePermissionTo(\Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'homework.view']));
        $this->teacher->schools()->attach($this->school->id);
        
        $this->student = User::factory()->create(['current_school_id' => $this->school->id]);
        $studentRole = Role::firstOrCreate(['name' => 'Student']);
        $this->student->assignRole($studentRole);
        $this->student->givePermissionTo(\Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'homework.view']));
        $this->student->givePermissionTo(\Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'homework.submit']));
        $this->student->schools()->attach($this->school->id);
        
        $this->schoolClass = SchoolClass::factory()->create(['school_id' => $this->school->id, 'name' => 'Class X']);
        $this->section = Section::factory()->create(['school_class_id' => $this->schoolClass->id, 'name' => 'Section A']);
        $this->subject = Subject::factory()->create(['school_id' => $this->school->id, 'name' => 'Maths', 'code' => 'M101']);
        
        $this->student->studentProfile()->create([
            'school_id' => $this->school->id,
            'admission_number' => 'ADM001',
            'admission_date' => now(),
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => '2010-01-01',
            'gender' => 'male',
        ]);
        
        $this->student->studentProfile->enrollments()->create([
            'school_id' => $this->school->id,
            'academic_session_id' => $this->session->id,
            'school_class_id' => $this->schoolClass->id,
            'section_id' => $this->section->id,
            'roll_number' => '1',
            'enrollment_date' => now(),
        ]);
        
        Storage::fake('local');
    }

    public function test_teacher_can_create_homework()
    {
        $this->withoutExceptionHandling();
        $file = UploadedFile::fake()->create('assignment.pdf', 100);
        
        $response = $this->actingAs($this->teacher)->post(route('academic.homework.store'), [
            'school_class_id' => $this->schoolClass->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'title' => 'Math Assignment',
            'description' => 'Complete chapter 1',
            'homework_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(2)->format('Y-m-d'),
            'attachment' => $file,
        ]);

        $response->assertRedirect(route('academic.homework.index'));
        
        $this->assertDatabaseHas('homework', [
            'school_class_id' => $this->schoolClass->id,
            'title' => 'Math Assignment',
            'teacher_id' => $this->teacher->id,
        ]);
        
        $homework = Homework::first();
        Storage::disk('local')->assertExists($homework->attachment_path);
    }

    public function test_student_can_view_assigned_homework()
    {
        $homework = Homework::create([
            'school_id' => $this->school->id,
            'academic_session_id' => $this->session->id,
            'school_class_id' => $this->schoolClass->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Math Assignment',
            'description' => 'Complete chapter 1',
            'homework_date' => now(),
            'due_date' => now()->addDays(2),
        ]);

        $response = $this->actingAs($this->student)->get(route('academic.homework.index'));
        
        $response->assertStatus(200);
        $response->assertSee('Math Assignment');
    }
    
    public function test_student_can_submit_homework()
    {
        $homework = Homework::create([
            'school_id' => $this->school->id,
            'academic_session_id' => $this->session->id,
            'school_class_id' => $this->schoolClass->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Math Assignment',
            'description' => 'Complete chapter 1',
            'homework_date' => now(),
            'due_date' => now()->addDays(2),
        ]);

        $file = UploadedFile::fake()->create('answer.pdf', 100);

        $response = $this->actingAs($this->student)->post(route('academic.homework.submissions.store', $homework), [
            'submission_text' => 'Here is my answer',
            'attachment' => $file,
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('homework_submissions', [
            'homework_id' => $homework->id,
            'student_id' => $this->student->studentProfile->id,
            'submission_text' => 'Here is my answer',
            'status' => 'submitted',
        ]);
    }
    
    public function test_teacher_can_evaluate_submission()
    {
        $homework = Homework::create([
            'school_id' => $this->school->id,
            'academic_session_id' => $this->session->id,
            'school_class_id' => $this->schoolClass->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Math Assignment',
            'description' => 'Complete chapter 1',
            'homework_date' => now(),
            'due_date' => now()->addDays(2),
        ]);

        $submission = HomeworkSubmission::create([
            'school_id' => $this->school->id,
            'homework_id' => $homework->id,
            'student_id' => $this->student->studentProfile->id,
            'submission_text' => 'My answer',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->teacher)->patch(route('academic.homework.submissions.evaluate', $submission), [
            'marks' => 85,
            'teacher_feedback' => 'Good job',
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('homework_submissions', [
            'id' => $submission->id,
            'marks' => 85,
            'teacher_feedback' => 'Good job',
            'status' => 'evaluated',
        ]);
    }

    private function makeHomework(array $overrides = []): Homework
    {
        return Homework::create($overrides + [
            'school_id' => $this->school->id,
            'academic_session_id' => $this->session->id,
            'school_class_id' => $this->schoolClass->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Math Assignment',
            'description' => 'Complete chapter 1',
            'homework_date' => now(),
            'due_date' => now()->addDays(2),
        ]);
    }

    public function test_student_sees_own_submission_and_teacher_sees_student_name()
    {
        $homework = $this->makeHomework();

        $this->actingAs($this->student)->post(route('academic.homework.submissions.store', $homework), ['submission_text' => 'First try']);
        // Re-submitting updates the same row instead of creating a second one
        $this->actingAs($this->student)->post(route('academic.homework.submissions.store', $homework), ['submission_text' => 'Second try']);

        $this->assertSame(1, HomeworkSubmission::count());
        $submission = HomeworkSubmission::first();
        $this->assertSame('Second try', $submission->submission_text);
        $this->assertSame('John Doe', $submission->student->full_name);

        $this->actingAs($this->student)->get(route('academic.homework.show', $homework))
            ->assertOk()
            ->assertSee('My Submission')
            ->assertSee('Submitted on');

        $this->actingAs($this->teacher)->get(route('academic.homework.show', $homework))
            ->assertOk()
            ->assertSee('John Doe');
    }

    public function test_student_downloads_own_attachment()
    {
        $homework = $this->makeHomework();
        $this->actingAs($this->student)->post(route('academic.homework.submissions.store', $homework), [
            'attachment' => UploadedFile::fake()->create('answer.pdf', 50),
        ]);

        $submission = HomeworkSubmission::firstOrFail();
        $this->actingAs($this->student)->get(route('academic.homework.submissions.download', $submission))
            ->assertOk()
            ->assertDownload('john-doe_submission.pdf');
    }

    public function test_student_of_another_class_cannot_submit()
    {
        $otherClass = SchoolClass::factory()->create(['school_id' => $this->school->id, 'name' => 'Class IX']);
        $otherSection = Section::factory()->create(['school_class_id' => $otherClass->id, 'name' => 'B']);
        $homework = $this->makeHomework(['school_class_id' => $otherClass->id, 'section_id' => $otherSection->id]);

        $this->actingAs($this->student)
            ->post(route('academic.homework.submissions.store', $homework), ['submission_text' => 'Sneaky'])
            ->assertForbidden();

        $this->assertSame(0, HomeworkSubmission::count());
    }

    public function test_user_without_student_record_cannot_submit()
    {
        $homework = $this->makeHomework();
        $noProfile = User::factory()->create(['current_school_id' => $this->school->id]);
        $noProfile->assignRole('Student');
        $noProfile->givePermissionTo('homework.submit');
        $noProfile->schools()->attach($this->school->id);

        $this->actingAs($noProfile)
            ->post(route('academic.homework.submissions.store', $homework), ['submission_text' => 'Hello'])
            ->assertForbidden();

        $this->assertSame(0, HomeworkSubmission::count());
    }

    public function test_teacher_can_create_homework_for_all_sections()
    {
        $this->actingAs($this->teacher)->post(route('academic.homework.store'), [
            'school_class_id' => $this->schoolClass->id,
            'section_id' => '',                     // All sections
            'subject_id' => $this->subject->id,
            'title' => 'Whole class task',
            'description' => 'For everyone',
            'homework_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(2)->format('Y-m-d'),
        ])->assertRedirect(route('academic.homework.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('homework', ['title' => 'Whole class task', 'section_id' => null]);
    }

    public function test_class_wide_homework_is_visible_to_students_in_any_section()
    {
        // A whole-class homework (no section)
        $classWide = $this->makeHomework(['section_id' => null, 'title' => 'Everyone reads chapter 2']);

        // A student in a DIFFERENT section of the same class
        $sectionB = Section::factory()->create(['school_class_id' => $this->schoolClass->id, 'name' => 'B']);
        $userB = User::factory()->create(['current_school_id' => $this->school->id]);
        $userB->assignRole('Student');
        $userB->givePermissionTo('homework.view');
        $userB->schools()->attach($this->school->id);
        $profileB = $userB->studentProfile()->create([
            'school_id' => $this->school->id, 'admission_number' => 'ADM-B', 'first_name' => 'Bina', 'last_name' => 'Roy',
            'date_of_birth' => '2011-01-01', 'gender' => 'female', 'admission_date' => '2026-04-01', 'status' => 'active',
        ]);
        $profileB->enrollments()->create([
            'school_id' => $this->school->id, 'academic_session_id' => $this->session->id,
            'school_class_id' => $this->schoolClass->id, 'section_id' => $sectionB->id, 'roll_number' => '1',
        ]);

        // Section B student sees the whole-class homework
        $this->actingAs($userB)->get(route('academic.homework.index'))
            ->assertOk()->assertSee('Everyone reads chapter 2');

        // And can submit it (not blocked by section)
        $userB->givePermissionTo('homework.submit');
        $this->actingAs($userB)
            ->post(route('academic.homework.submissions.store', $classWide), ['submission_text' => 'Done'])
            ->assertRedirect();
        $this->assertDatabaseHas('homework_submissions', ['homework_id' => $classWide->id, 'student_id' => $profileB->id]);
    }

    public function test_homework_section_must_belong_to_the_class()
    {
        $otherClass = SchoolClass::factory()->create(['school_id' => $this->school->id, 'name' => 'Class IX']);
        $foreignSection = Section::factory()->create(['school_class_id' => $otherClass->id, 'name' => 'Z']);

        $this->actingAs($this->teacher)->post(route('academic.homework.store'), [
            'school_class_id' => $this->schoolClass->id,
            'section_id' => $foreignSection->id,
            'subject_id' => $this->subject->id,
            'title' => 'Bad', 'description' => 'x',
            'homework_date' => now()->format('Y-m-d'), 'due_date' => now()->addDays(2)->format('Y-m-d'),
        ])->assertSessionHasErrors('section_id');
    }
}
