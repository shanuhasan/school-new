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
        $response = $this->actingAs($user2)->get('/students/' . $student->id);
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
        
        $response->assertRedirect(route('students.show', $student));
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

        $response = $this->actingAs($this->user)->post("/students/{$student->id}/enrollments", [
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

        $response = $this->actingAs($this->user)->post("/students/{$student->id}/documents", [
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
