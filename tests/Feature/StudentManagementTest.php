<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Guardian;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Models\StudentDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->user = User::factory()->create(['current_school_id' => $this->school->id]);
        $this->user->schools()->attach($this->school->id);
    }

    public function test_can_view_students_list()
    {
        $response = $this->actingAs($this->user)->get('/students');
        $response->assertStatus(200);
    }

    public function test_can_create_student()
    {
        $studentData = [
            'admission_number' => 'ADM-001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => '2010-01-01',
            'gender' => 'male',
            'admission_date' => '2023-09-01',
            'status' => 'active',
        ];

        $response = $this->actingAs($this->user)->post('/students', $studentData);
        
        $this->assertDatabaseHas('students', [
            'admission_number' => 'ADM-001',
            'school_id' => $this->school->id,
            'first_name' => 'John',
        ]);
        
        $student = Student::where('admission_number', 'ADM-001')->first();
        $response->assertRedirect(route('students.show', $student));
    }

    public function test_tenant_isolation_for_students()
    {
        // Create another school and user
        $school2 = School::factory()->create();
        $user2 = User::factory()->create(['current_school_id' => $school2->id]);
        $user2->schools()->attach($school2->id);

        // User 1 creates student
        $this->actingAs($this->user)->post('/students', [
            'admission_number' => 'ADM-002',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'date_of_birth' => '2011-02-02',
            'gender' => 'female',
            'admission_date' => '2023-09-01',
            'status' => 'active',
        ]);

        $student = Student::where('admission_number', 'ADM-002')->first();

        // User 2 should not see User 1's student
        $response = $this->actingAs($user2)->get('/students/' . $student->uuid);
        $response->assertStatus(404); // Global scope should hide it
    }

    public function test_can_add_guardian_to_student()
    {
        $student = Student::create([
            'school_id' => $this->school->id,
            'admission_number' => 'ADM-003',
            'first_name' => 'Timmy',
            'last_name' => 'Turner',
            'date_of_birth' => '2012-03-03',
            'gender' => 'male',
            'admission_date' => '2023-09-01',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->post('/guardians', [
            'first_name' => 'Mr',
            'last_name' => 'Turner',
            'phone' => '1234567890',
            'student_id' => $student->id,
            'relationship' => 'Father',
            'is_emergency_contact' => 1,
        ]);

        $this->assertDatabaseHas('guardians', ['first_name' => 'Mr', 'last_name' => 'Turner']);
        $this->assertDatabaseHas('guardian_student', [
            'student_id' => $student->id,
            'relationship' => 'Father',
            'is_emergency_contact' => 1,
        ]);
        
        $response->assertRedirect(route('students.show', ['student' => $student, 'tab' => 'guardians']));
    }

    private function makeStudent(string $admission, ?int $schoolId = null): Student
    {
        return Student::create([
            'school_id' => $schoolId ?? $this->school->id,
            'admission_number' => $admission,
            'first_name' => 'Kid',
            'last_name' => $admission,
            'date_of_birth' => '2014-01-01',
            'gender' => 'female',
            'admission_date' => '2023-09-01',
            'status' => 'active',
        ]);
    }

    private function makeGuardian(?int $schoolId = null, string $phone = '9000000001'): Guardian
    {
        return Guardian::create([
            'school_id' => $schoolId ?? $this->school->id,
            'first_name' => 'Asha',
            'last_name' => 'Verma',
            'phone' => $phone,
        ]);
    }

    public function test_create_form_lists_existing_guardians_not_yet_linked()
    {
        $sibling = $this->makeStudent('ADM-S1');
        $student = $this->makeStudent('ADM-S2');
        $guardian = $this->makeGuardian();
        $sibling->guardians()->attach($guardian->id, ['relationship' => 'Mother']);

        $this->actingAs($this->user)
            ->get(route('guardians.create', ['student' => $student->uuid]))
            ->assertOk()
            ->assertSee('Choose existing')
            ->assertSee('Asha Verma')
            ->assertSee('Guardian of Kid ADM-S1');
    }

    public function test_can_link_existing_guardian_to_second_student()
    {
        $sibling = $this->makeStudent('ADM-S1');
        $student = $this->makeStudent('ADM-S2');
        $guardian = $this->makeGuardian();
        $sibling->guardians()->attach($guardian->id, ['relationship' => 'Mother']);

        $response = $this->actingAs($this->user)->post(route('students.guardians.store', $student), [
            'guardian_id' => $guardian->id,
            'relationship' => 'Mother',
            'is_emergency_contact' => 1,
        ]);

        $response->assertRedirect(route('students.show', ['student' => $student, 'tab' => 'guardians']));
        $this->assertDatabaseHas('guardian_student', ['guardian_id' => $guardian->id, 'student_id' => $student->id, 'is_emergency_contact' => 1]);
        $this->assertSame(1, Guardian::count(), 'Linking must not create a duplicate guardian record');
    }

    public function test_cannot_link_same_guardian_twice()
    {
        $student = $this->makeStudent('ADM-S1');
        $guardian = $this->makeGuardian();
        $student->guardians()->attach($guardian->id, ['relationship' => 'Mother']);

        $this->actingAs($this->user)
            ->from(route('guardians.create', ['student' => $student->uuid]))
            ->post(route('students.guardians.store', $student), ['guardian_id' => $guardian->id, 'relationship' => 'Mother'])
            ->assertSessionHasErrors('guardian_id');

        $this->assertSame(1, $student->guardians()->count());
    }

    public function test_cannot_link_guardian_from_another_school()
    {
        $otherSchool = School::factory()->create();
        $foreignGuardian = $this->makeGuardian($otherSchool->id);
        $student = $this->makeStudent('ADM-S1');

        $this->actingAs($this->user)
            ->post(route('students.guardians.store', $student), ['guardian_id' => $foreignGuardian->id, 'relationship' => 'Father'])
            ->assertSessionHasErrors('guardian_id');

        $this->assertDatabaseMissing('guardian_student', ['guardian_id' => $foreignGuardian->id]);
    }

    public function test_can_unlink_guardian_without_deleting_it()
    {
        $student = $this->makeStudent('ADM-S1');
        $guardian = $this->makeGuardian();
        $student->guardians()->attach($guardian->id, ['relationship' => 'Mother']);

        $this->actingAs($this->user)
            ->delete(route('students.guardians.destroy', [$student, $guardian]))
            ->assertRedirect(route('students.show', ['student' => $student, 'tab' => 'guardians']));

        $this->assertDatabaseMissing('guardian_student', ['guardian_id' => $guardian->id, 'student_id' => $student->id]);
        $this->assertDatabaseHas('guardians', ['id' => $guardian->id]);
    }

    public function test_creating_guardian_with_existing_phone_warns_first_then_allows_on_confirm()
    {
        $student = $this->makeStudent('ADM-S1');
        $this->makeGuardian(null, '9000000001');

        $payload = [
            'first_name' => 'Asha',
            'last_name' => 'Verma',
            'phone' => '9000000001',
            'student_id' => $student->id,
            'relationship' => 'Mother',
        ];

        $this->actingAs($this->user)->post('/guardians', $payload)->assertSessionHasErrors('phone');
        $this->assertSame(1, Guardian::count());

        $this->actingAs($this->user)->post('/guardians', $payload + ['confirm_duplicate' => 1])->assertSessionHasNoErrors();
        $this->assertSame(2, Guardian::count());
    }

    public function test_can_enroll_student()
    {
        $student = Student::create([
            'school_id' => $this->school->id,
            'admission_number' => 'ADM-004',
            'first_name' => 'Jimmy',
            'last_name' => 'Neutron',
            'date_of_birth' => '2010-04-04',
            'gender' => 'male',
            'admission_date' => '2023-09-01',
            'status' => 'active',
        ]);

        $session = AcademicSession::create(['school_id' => $this->school->id, 'name' => '2023-2024', 'start_date' => '2023-09-01', 'end_date' => '2024-06-30']);
        $class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Grade 10']);

        $response = $this->actingAs($this->user)->post("/students/{$student->uuid}/enrollments", [
            'academic_session_id' => $session->id,
            'school_class_id' => $class->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'academic_session_id' => $session->id,
            'school_class_id' => $class->id,
        ]);
    }

    public function test_enrollment_accepts_section_of_chosen_class_and_rejects_other_class_section()
    {
        $student = $this->makeStudent('ADM-E1');
        $session = AcademicSession::create(['school_id' => $this->school->id, 'name' => '2026-2027', 'start_date' => '2026-04-01', 'end_date' => '2027-03-31']);
        $grade5 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Grade 5']);
        $grade6 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Grade 6']);
        $grade5A = \App\Models\Section::create(['school_class_id' => $grade5->id, 'name' => 'A']);
        $grade6B = \App\Models\Section::create(['school_class_id' => $grade6->id, 'name' => 'B']);

        // Section from a different class is rejected
        $this->actingAs($this->user)
            ->post(route('students.enrollments.store', $student), [
                'academic_session_id' => $session->id,
                'school_class_id' => $grade5->id,
                'section_id' => $grade6B->id,
                'status' => 'active',
            ])
            ->assertSessionHasErrorsIn('enrollment', 'section_id');
        $this->assertDatabaseCount('enrollments', 0);

        // Matching section is accepted
        $this->actingAs($this->user)
            ->post(route('students.enrollments.store', $student), [
                'academic_session_id' => $session->id,
                'school_class_id' => $grade5->id,
                'section_id' => $grade5A->id,
                'status' => 'active',
            ])
            ->assertRedirect(route('students.show', ['student' => $student, 'tab' => 'enrollments']));
        $this->assertDatabaseHas('enrollments', ['student_id' => $student->id, 'section_id' => $grade5A->id]);
    }

    public function test_student_page_provides_sections_grouped_by_class()
    {
        $student = $this->makeStudent('ADM-E2');
        $grade5 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Grade 5']);
        $section = \App\Models\Section::create(['school_class_id' => $grade5->id, 'name' => 'Rose']);

        $this->actingAs($this->user)
            ->get(route('students.show', $student))
            ->assertOk()
            ->assertSee('enroll-sections-data', false)
            ->assertSee('"' . $grade5->id . '":[{"id":' . $section->id . ',"name":"Rose"}]', false);
    }

    public function test_document_security_and_upload()
    {
        Storage::fake('local');

        $student = Student::create([
            'school_id' => $this->school->id,
            'admission_number' => 'ADM-005',
            'first_name' => 'Dexter',
            'last_name' => 'Boy',
            'date_of_birth' => '2012-05-05',
            'gender' => 'male',
            'admission_date' => '2023-09-01',
            'status' => 'active',
        ]);

        $file = UploadedFile::fake()->create('birth_certificate.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->user)->post("/students/{$student->uuid}/documents", [
            'title' => 'Birth Certificate',
            'document' => $file,
        ]);

        $document = StudentDocument::where('student_id', $student->id)->first();
        $this->assertNotNull($document);
        $this->assertEquals('local', $document->disk);
        Storage::disk('local')->assertExists($document->file_path);

        // Test secure download
        $downloadResponse = $this->actingAs($this->user)->get("/documents/{$document->id}/download");
        $downloadResponse->assertStatus(200);
        $downloadResponse->assertHeader('content-disposition');
    }
}
