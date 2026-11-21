<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Guardian;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use ZipArchive;

class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private School $school;
    private AcademicSession $session;
    private SchoolClass $grade5;
    private Section $grade5A;

    private const HEADER = 'admission_number,first_name,last_name,date_of_birth,gender,admission_date,status,phone,email,address,blood_group,class,section,roll_number,guardian_first_name,guardian_last_name,guardian_phone,guardian_email,guardian_relationship';

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->user = User::factory()->create(['current_school_id' => $this->school->id]);
        $this->user->schools()->attach($this->school->id);

        $this->session = AcademicSession::create(['school_id' => $this->school->id, 'name' => '2026-27', 'start_date' => '2026-04-01', 'end_date' => '2027-03-31']);
        $this->grade5 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Grade 5']);
        $this->grade5A = Section::create(['school_class_id' => $this->grade5->id, 'name' => 'A']);
    }

    private function csv(array $lines, string $header = self::HEADER): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('students.csv', $header . "\n" . implode("\n", $lines) . "\n");
    }

    private function upload(UploadedFile $file, array $extra = [])
    {
        return $this->actingAs($this->user)->post(route('students.import.store'), [
            'file' => $file,
            'academic_session_id' => $this->session->id,
        ] + $extra);
    }

    public function test_import_page_and_template_download()
    {
        $this->actingAs($this->user)->get(route('students.import.create'))
            ->assertOk()
            ->assertSee('Import students')
            ->assertSee('Grade 5');

        $response = $this->actingAs($this->user)->get(route('students.import.template'));
        $response->assertOk();
        $this->assertStringContainsString('attachment; filename="student-import-template.csv"', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString(self::HEADER, $response->getContent());
    }

    public function test_imports_students_with_enrollment_and_shared_guardian()
    {
        $file = $this->csv([
            'ADM-1,Riya,Sharma,2015-06-12,female,2026-04-02,active,,,,B+,Grade 5,A,7,Anil,Sharma,9876500001,,Father',
            'ADM-2,Kabir,Sharma,14-02-2017,Male,,,,,,,grade 5,a,8,Anil,Sharma,9876500001,,father',
            'ADM-3,Meera,Iyer,03/09/2015,F,,,,,,,,,,,,,,',
        ]);

        $this->upload($file)->assertRedirect(route('students.import.create'))->assertSessionHas('import_report');

        $this->assertSame(3, Student::count());
        $this->assertDatabaseHas('students', ['admission_number' => 'ADM-2', 'date_of_birth' => '2017-02-14 00:00:00', 'gender' => 'male']);
        $this->assertDatabaseHas('students', ['admission_number' => 'ADM-3', 'gender' => 'female', 'status' => 'active']);

        $riya = Student::where('admission_number', 'ADM-1')->first();
        $this->assertDatabaseHas('enrollments', ['student_id' => $riya->id, 'academic_session_id' => $this->session->id, 'school_class_id' => $this->grade5->id, 'section_id' => $this->grade5A->id, 'roll_number' => '7']);

        // Siblings share one guardian record
        $this->assertSame(1, Guardian::count());
        $this->assertSame(2, Guardian::first()->students()->count());

        $report = session('import_report');
        $this->assertSame(['students' => 3, 'enrolled' => 2, 'guardians_created' => 1, 'guardians_linked' => 1, 'classes_created' => 0], $report['imported']);
    }

    public function test_reuses_guardian_already_in_school()
    {
        $existing = Guardian::create(['school_id' => $this->school->id, 'first_name' => 'Anil', 'last_name' => 'Sharma', 'phone' => '9876500001']);

        $this->upload($this->csv(['ADM-1,Riya,Sharma,2015-06-12,female,,,,,,,,,,Anil,Sharma,9876500001,,Father']));

        $this->assertSame(1, Guardian::count());
        $this->assertTrue($existing->students()->where('admission_number', 'ADM-1')->exists());
    }

    public function test_nothing_is_imported_when_any_row_is_invalid()
    {
        Student::create(['school_id' => $this->school->id, 'admission_number' => 'ADM-9', 'first_name' => 'Old', 'last_name' => 'Kid', 'date_of_birth' => '2014-01-01', 'gender' => 'male', 'admission_date' => '2024-01-01', 'status' => 'active']);

        $this->upload($this->csv([
            'ADM-1,Riya,Sharma,2015-06-12,female,,,,,,,,,,,,,,',          // row 2 ok
            'ADM-9,Dup,Kid,2015-06-12,female,,,,,,,,,,,,,,',              // row 3 exists already
            'ADM-1,Again,Kid,2015-06-12,female,,,,,,,,,,,,,,',            // row 4 repeated in file
            'ADM-4,Bad,Date,31-31-2015,unknown,,,,,,,Grade 9,,,,,,,',     // row 5 bad date, gender, class
            'ADM-5,No,Section,2015-06-12,male,,,,,,,Grade 5,Z,,,,,,',     // row 6 section not in class
            'ADM-6,Guard,Less,2015-06-12,male,,,,,,,,,,Anil,,,,',         // row 7 guardian missing phone + relationship
        ]))->assertSessionHas('import_report');

        $this->assertSame(1, Student::count(), 'All-or-nothing: no rows may be saved');

        $errors = session('import_report')['errors'];
        $this->assertSame([3, 4, 5, 6, 7], array_keys($errors));
        $this->assertStringContainsString('already exists', $errors[3][0]);
        $this->assertStringContainsString('repeated (also on row 2)', $errors[4][0]);
        $this->assertStringContainsString('31-31-2015', implode(' ', $errors[5]));
        $this->assertStringContainsString('gender must be', implode(' ', $errors[5]));
        $this->assertStringContainsString('class "Grade 9" does not exist', implode(' ', $errors[5]));
        $this->assertStringContainsString('section "Z" does not belong', implode(' ', $errors[6]));
        $this->assertStringContainsString('guardian_phone is required', implode(' ', $errors[7]));
    }

    public function test_skip_invalid_imports_good_rows_only()
    {
        $this->upload($this->csv([
            'ADM-1,Riya,Sharma,2015-06-12,female,,,,,,,,,,,,,,',
            'ADM-2,,Sharma,2015-06-12,female,,,,,,,,,,,,,,',
        ]), ['skip_invalid' => 1]);

        $this->assertSame(1, Student::count());
        $this->assertSame(1, session('import_report')['error_count']);
        $this->assertSame(1, session('import_report')['imported']['students']);
    }

    public function test_missing_required_columns_is_rejected()
    {
        // No gender column → rejected (name + gender are the minimum)
        $this->upload($this->csv(['Riya,Sharma'], 'first_name,last_name'))
            ->assertSessionHasErrors('file');

        $this->assertStringContainsString('gender', session('errors')->first('file'));
        $this->assertSame(0, Student::count());
    }

    public function test_imports_messy_government_format_with_title_row_name_and_no_admission()
    {
        // Mimics a UDISE/SDMS "List of All Students" export: title + blank rows, single Name
        // column, no admission number or DOB, Roman-numeral class.
        $csv = "List of All Students (2026-27)\n\nClass,Section,Name,Gender,Father Name\n"
            . "I,A,ABDUL AHAD,Male,MO. AFZAL\n"
            . "I,A,INAYA,Female,PARVEJ ALAM\n";
        $file = \Illuminate\Http\Testing\File::createWithContent('students.csv', $csv);

        $session = AcademicSession::create(['school_id' => $this->school->id, 'name' => '2026-27', 'start_date' => '2026-04-01', 'end_date' => '2027-03-31', 'is_current' => true]);

        $this->actingAs($this->user)->post(route('students.import.store'), [
            'file' => $file, 'academic_session_id' => $session->id, 'create_missing' => 1,
        ])->assertRedirect(route('students.import.create'));

        $this->assertSame(2, Student::count());
        // Single-word name kept as first name only; two-word name split
        $this->assertDatabaseHas('students', ['first_name' => 'ABDUL', 'last_name' => 'AHAD', 'gender' => 'male', 'date_of_birth' => null]);
        $this->assertDatabaseHas('students', ['first_name' => 'INAYA', 'last_name' => null]);
        // Admission numbers were generated
        $this->assertStringStartsWith('ADM-', Student::where('first_name', 'INAYA')->value('admission_number'));
        // Class "I" and section "A" were created and students enrolled
        $class = \App\Models\SchoolClass::where('school_id', $this->school->id)->where('name', 'I')->first();
        $this->assertNotNull($class);
        $this->assertSame(2, \App\Models\Enrollment::where('school_class_id', $class->id)->count());
    }

    public function test_classes_of_another_school_are_not_matched()
    {
        $other = School::factory()->create();
        SchoolClass::create(['school_id' => $other->id, 'name' => 'Grade 7']);

        $this->upload($this->csv(['ADM-1,Riya,Sharma,2015-06-12,female,,,,,,,Grade 7,,,,,,,']));

        $this->assertSame(0, Student::count());
        $this->assertStringContainsString('class "Grade 7" does not exist', session('import_report')['errors'][2][0]);
    }

    public function test_imports_xlsx_file_with_excel_dates()
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>');
        $zip->addFromString('xl/sharedStrings.xml', '<?xml version="1.0"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<si><t>Admission Number*</t></si><si><t>First Name</t></si><si><t>Last Name</t></si><si><t>DOB</t></si><si><t>Gender</t></si><si><t>Class</t></si>'
            . '<si><t>XL-1</t></si><si><t>Aarav</t></si><si><t>Khan</t></si><si><t>male</t></si><si><t>Grade 5</t></si></sst>');
        // 42170 = 2015-06-15 as an Excel serial date
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'
            . '<row r="1"><c r="A1" t="s"><v>0</v></c><c r="B1" t="s"><v>1</v></c><c r="C1" t="s"><v>2</v></c><c r="D1" t="s"><v>3</v></c><c r="E1" t="s"><v>4</v></c><c r="F1" t="s"><v>5</v></c></row>'
            . '<row r="2"><c r="A2" t="s"><v>6</v></c><c r="B2" t="s"><v>7</v></c><c r="C2" t="s"><v>8</v></c><c r="D2"><v>42170</v></c><c r="E2" t="s"><v>9</v></c><c r="F2" t="s"><v>10</v></c></row>'
            . '</sheetData></worksheet>');
        $zip->close();

        $file = new UploadedFile($path, 'students.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $this->upload($file)->assertSessionHasNoErrors();

        $this->assertDatabaseHas('students', ['admission_number' => 'XL-1', 'first_name' => 'Aarav', 'date_of_birth' => '2015-06-15 00:00:00']);
        $this->assertDatabaseHas('enrollments', ['school_class_id' => $this->grade5->id]);
    }
}
