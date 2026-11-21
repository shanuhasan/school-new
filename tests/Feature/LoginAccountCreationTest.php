<?php

namespace Tests\Feature;

use App\Actions\CreateLoginAccount;
use App\Facades\Tenant;
use App\Models\Guardian;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginAccountCreationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->school = School::factory()->create();
        Tenant::setSchool($this->school);

        $this->admin = User::factory()->create(['current_school_id' => $this->school->id]);
        $this->admin->assignRole('School Admin');
        $this->admin->schools()->attach($this->school->id);
    }

    private function makeStudent(array $attrs = []): Student
    {
        return Student::create($attrs + [
            'school_id' => $this->school->id, 'admission_number' => 'ADM-1', 'first_name' => 'Riya', 'last_name' => 'Rao',
            'date_of_birth' => '2015-01-01', 'gender' => 'female', 'admission_date' => '2026-04-01', 'status' => 'active',
        ]);
    }

    public function test_action_creates_linked_student_account_with_role_and_can_sign_in()
    {
        $student = $this->makeStudent(['email' => 'riya@school.test']);

        $result = app(CreateLoginAccount::class)->forStudent($student);

        $this->assertFalse($result['existed']);
        $this->assertNotNull($result['password']);
        $student->refresh();
        $this->assertSame($result['user']->id, $student->user_id);
        $this->assertTrue($result['user']->hasRole('Student'));
        $this->assertTrue($result['user']->schools()->whereKey($this->school->id)->exists());
        $this->assertTrue(Hash::check($result['password'], $result['user']->password));
        $this->assertNotNull($result['user']->email_verified_at);
    }

    public function test_action_is_idempotent_and_rejects_missing_or_duplicate_email()
    {
        $student = $this->makeStudent(['email' => 'riya@school.test']);
        $first = app(CreateLoginAccount::class)->forStudent($student);

        // Second call returns the same account, creates nothing new
        $second = app(CreateLoginAccount::class)->forStudent($student->fresh());
        $this->assertTrue($second['existed']);
        $this->assertSame($first['user']->id, $second['user']->id);
        $this->assertSame(1, User::where('email', 'riya@school.test')->count());

        // No email → error
        $noEmail = $this->makeStudent(['admission_number' => 'ADM-2']);
        $this->expectException(\RuntimeException::class);
        app(CreateLoginAccount::class)->forStudent($noEmail);
    }

    public function test_duplicate_email_is_rejected()
    {
        User::factory()->create(['email' => 'taken@school.test']);
        $student = $this->makeStudent(['email' => 'taken@school.test']);

        $this->expectException(\RuntimeException::class);
        app(CreateLoginAccount::class)->forGuardian(Guardian::create([
            'school_id' => $this->school->id, 'first_name' => 'A', 'last_name' => 'B', 'phone' => '9', 'email' => 'taken@school.test',
        ]));
    }

    public function test_creating_student_via_form_with_login_account()
    {
        $this->actingAs($this->admin)->post(route('students.store'), [
            'admission_number' => 'ADM-9', 'first_name' => 'Kabir', 'last_name' => 'Rao',
            'date_of_birth' => '2015-01-01', 'gender' => 'male', 'admission_date' => '2026-04-01', 'status' => 'active',
            'email' => 'kabir@school.test',
            'create_login' => '1', 'login_password' => 'secret12345',
        ])->assertRedirect()->assertSessionHas('new_login');

        $student = Student::where('admission_number', 'ADM-9')->first();
        $this->assertNotNull($student->user_id);
        $this->assertTrue($student->user->hasRole('Student'));
        $this->assertTrue(Hash::check('secret12345', $student->user->password));
    }

    public function test_create_login_for_existing_student_from_profile()
    {
        $student = $this->makeStudent(['email' => 'riya@school.test']);

        $this->actingAs($this->admin)->post(route('students.login-account', $student))
            ->assertRedirect()->assertSessionHas('new_login');

        $this->assertNotNull($student->fresh()->user_id);

        // Second attempt tells the admin it already exists
        $this->actingAs($this->admin)->post(route('students.login-account', $student->fresh()))
            ->assertSessionHas('error');
    }

    public function test_create_login_without_email_is_refused()
    {
        $student = $this->makeStudent(); // no email

        $this->actingAs($this->admin)->post(route('students.login-account', $student))
            ->assertSessionHas('error');
        $this->assertNull($student->fresh()->user_id);
    }

    public function test_guardian_account_gets_parent_role()
    {
        $guardian = Guardian::create(['school_id' => $this->school->id, 'first_name' => 'Asha', 'last_name' => 'Rao', 'phone' => '9', 'email' => 'asha@school.test']);

        $this->actingAs($this->admin)->post(route('guardians.login-account', $guardian))
            ->assertRedirect()->assertSessionHas('new_login');

        $guardian->refresh();
        $this->assertTrue($guardian->user->hasRole('Parent'));
    }

    public function test_student_profile_shows_create_login_button_then_active()
    {
        $student = $this->makeStudent(['email' => 'riya@school.test']);

        $this->actingAs($this->admin)->get(route('students.show', $student))
            ->assertOk()->assertSee('Create login account');

        app(CreateLoginAccount::class)->forStudent($student);

        $this->actingAs($this->admin)->get(route('students.show', $student->fresh()))
            ->assertOk()->assertSee('Account active')->assertDontSee('Create login account');
    }
}
