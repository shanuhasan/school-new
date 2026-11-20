<?php

namespace Tests\Feature;

use App\Facades\Tenant;
use App\Models\Guardian;
use App\Models\School;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GuardianTeacherUuidTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        Tenant::setSchool($this->school);

        Permission::firstOrCreate(['name' => 'staff.manage']);
        $role = Role::firstOrCreate(['name' => 'School Admin']);
        $role->givePermissionTo('staff.manage');
        Role::firstOrCreate(['name' => 'Teacher']); // assigned by the Add Teacher form (seeded in real installs)

        $this->admin = User::factory()->create(['current_school_id' => $this->school->id]);
        $this->admin->assignRole($role);
        $this->admin->schools()->attach($this->school);
    }

    private function guardian(array $attrs = []): Guardian
    {
        return Guardian::create($attrs + ['school_id' => $this->school->id, 'first_name' => 'Asha', 'last_name' => 'Rao', 'phone' => '9000000001']);
    }

    /* ---------------- Guardians ---------------- */

    public function test_guardian_gets_guid_on_create_and_import()
    {
        $this->actingAs($this->admin)->post(route('guardians.store'), ['first_name' => 'Asha', 'last_name' => 'Rao', 'phone' => '9000000001']);
        $this->assertTrue(Str::isUuid(Guardian::where('phone', '9000000001')->value('uuid')));

        $csv = "first_name,last_name,phone\nRavi,Rao,9000000002\n";
        $this->actingAs($this->admin)->post(route('guardians.import.store'), ['file' => UploadedFile::fake()->createWithContent('g.csv', $csv)]);
        $this->assertTrue(Str::isUuid(Guardian::where('phone', '9000000002')->value('uuid')));
    }

    public function test_guardian_urls_use_guid_only()
    {
        $g = $this->guardian();

        $this->assertSame(url("/guardians/{$g->uuid}/edit"), route('guardians.edit', $g));
        $this->actingAs($this->admin)->get(route('guardians.show', $g))->assertOk();
        $this->actingAs($this->admin)->get(route('guardians.edit', $g))->assertOk()->assertSee('Asha');
        $this->actingAs($this->admin)->get("/guardians/{$g->id}")->assertNotFound();
        $this->actingAs($this->admin)->get("/guardians/{$g->id}/edit")->assertNotFound();

        $this->actingAs($this->admin)->get(route('guardians.index'))
            ->assertSee("/guardians/{$g->uuid}/edit", false)
            ->assertDontSee("/guardians/{$g->id}/edit", false);
    }

    public function test_guardian_update_keeps_guid_and_unlink_uses_guids()
    {
        $g = $this->guardian();
        $uuid = $g->uuid;

        $this->actingAs($this->admin)
            ->put(route('guardians.update', $g), ['first_name' => 'Asha', 'last_name' => 'Verma', 'phone' => '9000000001'])
            ->assertSessionHasNoErrors();
        $this->assertSame(['Verma', $uuid], [$g->fresh()->last_name, $g->fresh()->uuid]);

        $student = Student::create(['school_id' => $this->school->id, 'admission_number' => 'A-1', 'first_name' => 'Kid', 'last_name' => 'Rao',
            'date_of_birth' => '2015-01-01', 'gender' => 'male', 'admission_date' => '2026-04-01', 'status' => 'active']);
        $student->guardians()->attach($g->id, ['relationship' => 'Mother']);

        $url = route('students.guardians.destroy', [$student, $g]);
        $this->assertStringContainsString("/students/{$student->uuid}/guardians/{$g->uuid}", $url);
        $this->actingAs($this->admin)->delete($url)->assertRedirect();
        $this->assertSame(0, $student->guardians()->count());
    }

    public function test_guardian_guid_from_another_school_is_not_found()
    {
        $foreign = $this->guardian(['school_id' => School::factory()->create()->id, 'phone' => '1']);

        $this->actingAs($this->admin)->get(route('guardians.edit', $foreign))->assertNotFound();
    }

    /* ---------------- Teachers ---------------- */

    public function test_teacher_gets_guid_on_create_and_import()
    {
        $this->actingAs($this->admin)->post(route('staff.teachers.store'), [
            'first_name' => 'Vikram', 'last_name' => 'Singh', 'email' => 'vikram@school.test', 'status' => 'active',
        ]);
        $this->assertTrue(Str::isUuid(Teacher::whereHas('user', fn ($q) => $q->where('email', 'vikram@school.test'))->value('uuid')));

        $csv = "first_name,last_name,email\nAnita,Rao,anita@school.test\n";
        $this->actingAs($this->admin)->post(route('staff.teachers.import.store'), ['file' => UploadedFile::fake()->createWithContent('t.csv', $csv)]);
        $this->assertTrue(Str::isUuid(Teacher::whereHas('user', fn ($q) => $q->where('email', 'anita@school.test'))->value('uuid')));
    }

    public function test_teacher_urls_use_guid_only_and_edit_keeps_it()
    {
        $teacher = Teacher::factory()->create(['school_id' => $this->school->id]);
        $uuid = $teacher->uuid;

        $this->assertTrue(Str::isUuid($uuid));
        $this->assertSame(url("/staff/teachers/{$uuid}/edit"), route('staff.teachers.edit', $teacher));
        $this->actingAs($this->admin)->get(route('staff.teachers.show', $teacher))->assertOk();
        $this->actingAs($this->admin)->get(route('staff.teachers.edit', $teacher))->assertOk();
        $this->actingAs($this->admin)->get("/staff/teachers/{$teacher->id}")->assertNotFound();
        $this->actingAs($this->admin)->get("/staff/teachers/{$teacher->id}/edit")->assertNotFound();

        $this->actingAs($this->admin)->get(route('staff.teachers.index'))
            ->assertSee("/staff/teachers/{$uuid}/edit", false)
            ->assertDontSee("/staff/teachers/{$teacher->id}/edit", false);

        $teacher->update(['qualification' => 'PhD']);
        $this->assertSame($uuid, $teacher->fresh()->uuid);
    }
}
