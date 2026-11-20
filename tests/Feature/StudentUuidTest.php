<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudentUuidTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->user = User::factory()->create(['current_school_id' => $this->school->id]);
        $this->user->schools()->attach($this->school->id);
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'admission_number' => 'ADM-500',
            'first_name' => 'Riya',
            'last_name' => 'Sharma',
            'date_of_birth' => '2015-06-12',
            'gender' => 'female',
            'admission_date' => '2026-04-02',
            'status' => 'active',
        ];
    }

    public function test_creating_a_student_generates_a_guid_and_redirects_to_it()
    {
        $response = $this->actingAs($this->user)->post(route('students.store'), $this->payload());

        $student = Student::where('admission_number', 'ADM-500')->firstOrFail();
        $this->assertTrue(Str::isUuid($student->uuid));
        $this->assertIsInt($student->id, 'Numeric id is still the primary key');
        $response->assertRedirect('/students/' . $student->uuid);
    }

    public function test_every_student_gets_a_unique_guid_including_imports()
    {
        $a = Student::create($this->payload(['school_id' => $this->school->id, 'admission_number' => 'A-1']));
        $b = Student::create($this->payload(['school_id' => $this->school->id, 'admission_number' => 'A-2']));
        $this->assertNotSame($a->uuid, $b->uuid);

        $csv = "admission_number,first_name,last_name,date_of_birth,gender\nIMP-1,Kabir,Rao,2016-01-01,male\n";
        $this->actingAs($this->user)->post(route('students.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('s.csv', $csv),
        ]);
        $this->assertTrue(Str::isUuid(Student::where('admission_number', 'IMP-1')->value('uuid')));
    }

    public function test_urls_use_the_guid_and_numeric_ids_no_longer_work()
    {
        $student = Student::create($this->payload(['school_id' => $this->school->id]));

        $this->assertSame(url("/students/{$student->uuid}/edit"), route('students.edit', $student));

        $this->actingAs($this->user)->get(route('students.show', $student))->assertOk();
        $this->actingAs($this->user)->get("/students/{$student->uuid}/edit")->assertOk()->assertSee('Riya');

        $this->actingAs($this->user)->get("/students/{$student->id}")->assertNotFound();
        $this->actingAs($this->user)->get("/students/{$student->id}/edit")->assertNotFound();
        $this->actingAs($this->user)->get('/students/not-a-guid/edit')->assertNotFound();
    }

    public function test_edit_and_update_by_guid()
    {
        $student = Student::create($this->payload(['school_id' => $this->school->id]));
        $originalUuid = $student->uuid;

        $this->actingAs($this->user)
            ->put("/students/{$student->uuid}", $this->payload(['first_name' => 'Riyanshi']))
            ->assertSessionHasNoErrors();

        $student->refresh();
        $this->assertSame('Riyanshi', $student->first_name);
        $this->assertSame($originalUuid, $student->uuid, 'Editing never changes the GUID');
    }

    public function test_student_pages_link_by_guid_only()
    {
        $student = Student::create($this->payload(['school_id' => $this->school->id]));

        $this->actingAs($this->user)->get(route('students.index'))
            ->assertOk()
            ->assertSee("/students/{$student->uuid}/edit", false)
            ->assertDontSee("/students/{$student->id}/edit", false);

        $this->actingAs($this->user)->get(route('students.show', $student))
            ->assertOk()
            ->assertSee('guardians/create?student=' . $student->uuid, false)
            ->assertSee("/students/{$student->uuid}/enrollments", false);
    }

    public function test_guid_of_another_school_is_not_found()
    {
        $other = School::factory()->create();
        $foreign = Student::create($this->payload(['school_id' => $other->id, 'admission_number' => 'X-1']));

        $this->actingAs($this->user)->get(route('students.edit', $foreign))->assertNotFound();
        $this->actingAs($this->user)->get(route('guardians.create', ['student' => $foreign->uuid]))->assertNotFound();
    }
}
