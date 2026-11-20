<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Guardian;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use ZipArchive;

class StudentExportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create(['name' => 'Green Valley School']);
        $this->user = User::factory()->create(['current_school_id' => $this->school->id]);
        $this->user->schools()->attach($this->school->id);
    }

    private function student(string $adm, string $first, ?int $schoolId = null, string $status = 'active'): Student
    {
        return Student::create([
            'school_id' => $schoolId ?? $this->school->id,
            'admission_number' => $adm,
            'first_name' => $first,
            'last_name' => 'Rao',
            'date_of_birth' => '2015-06-12',
            'gender' => 'female',
            'phone' => '0987654321',
            'admission_date' => '2026-04-02',
            'status' => $status,
        ]);
    }

    /** Reads the downloaded workbook back with the importer's reader. */
    private function readExport($response): array
    {
        $path = tempnam(sys_get_temp_dir(), 'exp') . '.xlsx';
        file_put_contents($path, file_get_contents($response->baseResponse->getFile()->getPathname()));

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true, 'Export must be a valid zip/xlsx');
        $zip->close();

        return app(StudentImportService::class)->read(new UploadedFile($path, 'export.xlsx', null, null, true));
    }

    public function test_exports_students_with_class_and_guardian()
    {
        $session = AcademicSession::create(['school_id' => $this->school->id, 'name' => '2026-27', 'start_date' => '2026-04-01', 'end_date' => '2027-03-31', 'is_current' => true]);
        $class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Grade 5']);
        $section = Section::create(['school_class_id' => $class->id, 'name' => 'A']);

        $riya = $this->student('ADM-1', 'Riya');
        $riya->enrollments()->create(['school_id' => $this->school->id, 'academic_session_id' => $session->id, 'school_class_id' => $class->id, 'section_id' => $section->id, 'roll_number' => '7', 'status' => 'active']);
        $mother = Guardian::create(['school_id' => $this->school->id, 'first_name' => 'Asha', 'last_name' => 'Rao', 'phone' => '9000000001']);
        $father = Guardian::create(['school_id' => $this->school->id, 'first_name' => 'Ravi', 'last_name' => 'Rao', 'phone' => '9000000002']);
        $riya->guardians()->attach($father->id, ['relationship' => 'Father', 'is_emergency_contact' => false]);
        $riya->guardians()->attach($mother->id, ['relationship' => 'Mother', 'is_emergency_contact' => true]);

        $response = $this->actingAs($this->user)->get(route('students.export'));

        $response->assertOk();
        $this->assertStringContainsString('students-green-valley-school-', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition'));

        $rows = $this->readExport($response);
        $row = $rows[2];

        $this->assertSame('ADM-1', $row['admission_number']);
        $this->assertSame('0987654321', $row['phone'], 'Phone keeps its leading zero');
        $this->assertSame('2015-06-12', app(StudentImportService::class)->parseDate($row['date_of_birth']), 'Date is a real Excel date');
        $this->assertSame('Grade 5', $row['class']);
        $this->assertSame('A', $row['section']);
        $this->assertSame('7', $row['roll_number']);
        $this->assertSame('Asha', $row['guardian_first_name'], 'Emergency contact is preferred');
        $this->assertSame('Mother', $row['guardian_relationship']);
    }

    public function test_export_respects_filters_and_school()
    {
        $this->student('ADM-1', 'Riya');
        $this->student('ADM-2', 'Kabir', null, 'inactive');
        $this->student('ADM-3', 'Meera');

        $other = School::factory()->create();
        $this->student('OTHER-1', 'Outsider', $other->id);

        $all = $this->readExport($this->actingAs($this->user)->get(route('students.export')));
        $this->assertSame(['ADM-1', 'ADM-2', 'ADM-3'], array_values(array_column($all, 'admission_number')));

        $active = $this->readExport($this->actingAs($this->user)->get(route('students.export', ['status' => 'active'])));
        $this->assertSame(['ADM-1', 'ADM-3'], array_values(array_column($active, 'admission_number')));

        $search = $this->readExport($this->actingAs($this->user)->get(route('students.export', ['search' => 'Kab'])));
        $this->assertSame(['ADM-2'], array_values(array_column($search, 'admission_number')));
    }

    public function test_exported_file_can_be_reimported_into_another_school()
    {
        $this->student('ADM-1', 'Riya');
        $file = $this->actingAs($this->user)->get(route('students.export'));
        $rows = $this->readExport($file);

        $otherSchool = School::factory()->create();
        $result = app(StudentImportService::class)->validate($rows, $otherSchool->id, null);

        $this->assertSame([], $result['errors']);
        $this->assertCount(1, $result['valid']);
    }

    public function test_students_page_has_export_link_with_current_filters()
    {
        $this->actingAs($this->user)
            ->get(route('students.index', ['status' => 'active', 'search' => 'ri']))
            ->assertOk()
            ->assertSee(e(route('students.export', ['search' => 'ri', 'status' => 'active'])), false);
    }
}
