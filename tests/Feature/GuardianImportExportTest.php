<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\GuardianImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class GuardianImportExportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private School $school;
    private Student $kid1;
    private Student $kid2;

    private const HEADER = 'first_name,last_name,phone,email,occupation,address,student_admission_number,relationship,emergency_contact';

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create(['name' => 'Green Valley School']);
        $this->user = User::factory()->create(['current_school_id' => $this->school->id]);
        $this->user->schools()->attach($this->school->id);

        $this->kid1 = $this->student('ADM-1', 'Riya');
        $this->kid2 = $this->student('ADM-2', 'Kabir');
    }

    private function student(string $adm, string $first, ?int $schoolId = null): Student
    {
        return Student::create([
            'school_id' => $schoolId ?? $this->school->id, 'admission_number' => $adm, 'first_name' => $first,
            'last_name' => 'Sharma', 'date_of_birth' => '2015-06-12', 'gender' => 'female',
            'admission_date' => '2026-04-02', 'status' => 'active',
        ]);
    }

    private function upload(array $lines, array $extra = [], string $header = self::HEADER)
    {
        $file = UploadedFile::fake()->createWithContent('guardians.csv', $header . "\n" . implode("\n", $lines) . "\n");

        return $this->actingAs($this->user)->post(route('guardians.import.store'), ['file' => $file] + $extra);
    }

    public function test_import_page_and_template()
    {
        $this->actingAs($this->user)->get(route('guardians.import.create'))->assertOk()->assertSee('Import guardians');

        $response = $this->actingAs($this->user)->get(route('guardians.import.template'));
        $response->assertOk();
        $this->assertStringContainsString('guardian-import-template.csv', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString(self::HEADER, $response->getContent());
    }

    public function test_imports_guardians_and_links_children()
    {
        $this->upload([
            'Anil,Sharma,9876500001,anil@example.com,Engineer,,"ADM-1, ADM-2",father,yes',
            'Sunita,Sharma,9876500002,,,,ADM-1,Mother,no',
            'Rahul,Verma,9876500003,,,,,,',
        ])->assertRedirect(route('guardians.import.create'));

        $this->assertSame(3, Guardian::count());
        $anil = Guardian::where('phone', '9876500001')->first();
        $this->assertSame(['ADM-1', 'ADM-2'], $anil->students()->orderBy('admission_number')->pluck('admission_number')->all());
        $this->assertDatabaseHas('guardian_student', ['guardian_id' => $anil->id, 'student_id' => $this->kid1->id, 'relationship' => 'Father', 'is_emergency_contact' => 1]);
        $this->assertSame(['created' => 3, 'existing' => 0, 'links' => 3], session('import_report')['imported']);
    }

    public function test_existing_phone_reuses_guardian_and_repeated_rows_merge()
    {
        $existing = Guardian::create(['school_id' => $this->school->id, 'first_name' => 'Anil', 'last_name' => 'Sharma', 'phone' => '+91 98765-00001']);
        $existing->students()->attach($this->kid1->id, ['relationship' => 'Father']);

        $this->upload([
            'Anil,Sharma,919876500001,,,,ADM-1,Father,',   // already linked: no duplicate link
            'Anil,Sharma,919876500001,,,,ADM-2,Father,',   // same person, second child
        ]);

        $this->assertSame(1, Guardian::count(), 'No duplicate guardian created');
        $this->assertSame(2, $existing->students()->count());
        $this->assertSame(['created' => 0, 'existing' => 1, 'links' => 1], session('import_report')['imported']);
    }

    public function test_invalid_rows_block_import_and_are_reported()
    {
        $other = School::factory()->create();
        $this->student('OTHER-1', 'Outsider', $other->id);

        $this->upload([
            'Anil,Sharma,9876500001,,,,ADM-1,Father,yes',     // 2 ok
            'Asha,,9876500002,not-an-email,,,,,',              // 3 last name + email
            'Ravi,Rao,9876500003,,,,ADM-99,Father,',           // 4 unknown student
            'Ravi,Rao,9876500004,,,,ADM-1,,',                  // 5 relationship missing
            'Ravi,Rao,9876500005,,,,OTHER-1,Father,maybe',     // 6 other school + bad yes/no
            'Someone,Else,9876500001,,,,,,',                   // 7 same phone, different name
        ])->assertSessionHas('import_report');

        $this->assertSame(0, Guardian::count());
        $errors = session('import_report')['errors'];
        $this->assertSame([3, 4, 5, 6, 7], array_keys($errors));
        $this->assertStringContainsString('last_name is required', implode(' ', $errors[3]));
        $this->assertStringContainsString('email is not a valid', implode(' ', $errors[3]));
        $this->assertStringContainsString('"ADM-99"', implode(' ', $errors[4]));
        $this->assertStringContainsString('relationship is required', implode(' ', $errors[5]));
        $this->assertStringContainsString('"OTHER-1"', implode(' ', $errors[6]));
        $this->assertStringContainsString('emergency_contact must be yes or no', implode(' ', $errors[6]));
        $this->assertStringContainsString('also used on row 2', implode(' ', $errors[7]));
    }

    public function test_skip_invalid_imports_good_rows()
    {
        $this->upload(['Anil,Sharma,9876500001,,,,,,', ',,,,,,ADM-1,,'], ['skip_invalid' => 1]);

        $this->assertSame(1, Guardian::count());
        $this->assertSame(1, session('import_report')['error_count']);
    }

    public function test_export_one_row_per_link_and_reimport_roundtrip()
    {
        $anil = Guardian::create(['school_id' => $this->school->id, 'first_name' => 'Anil', 'last_name' => 'Sharma', 'phone' => '09876500001']);
        $anil->students()->attach($this->kid1->id, ['relationship' => 'Father', 'is_emergency_contact' => true]);
        $anil->students()->attach($this->kid2->id, ['relationship' => 'Father', 'is_emergency_contact' => false]);
        Guardian::create(['school_id' => $this->school->id, 'first_name' => 'Rahul', 'last_name' => 'Verma', 'phone' => '9876500003']);
        Guardian::create(['school_id' => School::factory()->create()->id, 'first_name' => 'Outsider', 'last_name' => 'X', 'phone' => '1']);

        $response = $this->actingAs($this->user)->get(route('guardians.export'));
        $response->assertOk();
        $this->assertStringContainsString('guardians-green-valley-school-', $response->headers->get('Content-Disposition'));

        $path = tempnam(sys_get_temp_dir(), 'g') . '.xlsx';
        copy($response->baseResponse->getFile()->getPathname(), $path);
        $service = app(GuardianImportService::class);
        $rows = array_values($service->read(new UploadedFile($path, 'g.xlsx', null, null, true)));

        $this->assertCount(3, $rows, 'Anil x2 children + Rahul; other school excluded');
        $this->assertSame(['Anil', '09876500001', 'ADM-1', 'Father', 'yes'], [$rows[0]['first_name'], $rows[0]['phone'], $rows[0]['student_admission_number'], $rows[0]['relationship'], $rows[0]['emergency_contact']]);
        $this->assertSame('ADM-2', $rows[1]['student_admission_number']);
        $this->assertSame('', $rows[2]['student_admission_number']);

        // Re-importing the export into the same school is clean and creates nothing new
        $result = $service->validate($rows, $this->school->id);
        $this->assertSame([], $result['errors']);
        $this->assertSame(['created' => 0, 'existing' => 2, 'links' => 0], $service->import($result['valid'], $this->school->id));
    }

    public function test_export_respects_search_and_page_links()
    {
        Guardian::create(['school_id' => $this->school->id, 'first_name' => 'Anil', 'last_name' => 'Sharma', 'phone' => '1']);
        Guardian::create(['school_id' => $this->school->id, 'first_name' => 'Rahul', 'last_name' => 'Verma', 'phone' => '2']);

        $response = $this->actingAs($this->user)->get(route('guardians.export', ['search' => 'Verma']));
        $path = tempnam(sys_get_temp_dir(), 'g') . '.xlsx';
        copy($response->baseResponse->getFile()->getPathname(), $path);
        $rows = app(GuardianImportService::class)->read(new UploadedFile($path, 'g.xlsx', null, null, true));
        $this->assertSame(['Rahul'], array_values(array_column($rows, 'first_name')));

        $this->actingAs($this->user)->get(route('guardians.index', ['search' => 'an']))
            ->assertOk()
            ->assertSee(e(route('guardians.export', ['search' => 'an'])), false)
            ->assertSee(route('guardians.import.create'), false);
    }
}
