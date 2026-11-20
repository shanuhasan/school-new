<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = School::factory()->create();
        Role::firstOrCreate(['name' => 'Super Admin']);
        Role::firstOrCreate(['name' => 'Teacher']);
    }

    private function superAdmin(): User
    {
        $u = User::factory()->create(['current_school_id' => $this->school->id, 'name' => 'Big Boss']);
        $u->assignRole('Super Admin');
        $u->schools()->attach($this->school->id);

        return $u;
    }

    private function member(string $role = 'Teacher', array $attrs = []): User
    {
        $u = User::factory()->create($attrs + ['current_school_id' => $this->school->id, 'name' => 'Normal User']);
        $u->assignRole($role);
        $u->schools()->attach($this->school->id);

        return $u;
    }

    public function test_super_admin_sees_users_page_and_can_start_and_stop()
    {
        $admin = $this->superAdmin();
        $target = $this->member();

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()->assertSee('Login as')->assertSee($target->email);

        $this->actingAs($admin)
            ->post(route('admin.impersonate.start', $target))
            ->assertRedirect(route('dashboard'));

        $this->assertSame($target->id, Auth::id());
        $this->assertSame($admin->id, session('impersonator_id'));

        // Impersonated pages show the banner with a Stop control
        $this->get(route('dashboard'))->assertSee('You are signed in as')->assertSee('Stop');

        $this->post(route('admin.impersonate.stop'))->assertRedirect(route('dashboard'));
        $this->assertSame($admin->id, Auth::id());
        $this->assertNull(session('impersonator_id'));
    }

    public function test_non_super_admin_cannot_use_impersonation()
    {
        $member = $this->member();
        $other = $this->member('Teacher', []);

        $this->actingAs($member)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($member)->post(route('admin.impersonate.start', $other))->assertForbidden();
        $this->assertSame($member->id, Auth::id());
    }

    public function test_cannot_impersonate_self_or_another_super_admin()
    {
        $admin = $this->superAdmin();
        $otherAdmin = $this->superAdmin();

        $this->actingAs($admin)->post(route('admin.impersonate.start', $admin))->assertRedirect();
        $this->assertNull(session('impersonator_id'));
        $this->assertSame($admin->id, Auth::id());

        $this->actingAs($admin)->post(route('admin.impersonate.start', $otherAdmin))
            ->assertSessionHas('error');
        $this->assertNull(session('impersonator_id'));
    }

    public function test_cannot_nest_impersonation()
    {
        $admin = $this->superAdmin();
        $a = $this->member();
        $b = $this->member();

        $this->actingAs($admin)->post(route('admin.impersonate.start', $a));
        $this->assertSame($a->id, Auth::id());

        // While impersonating A (a Teacher), the session is no longer a Super Admin,
        // so starting another impersonation is refused outright and we stay as A.
        $this->post(route('admin.impersonate.start', $b))->assertForbidden();
        $this->assertSame($a->id, Auth::id());
        $this->assertSame($admin->id, session('impersonator_id'));
    }

    public function test_stop_without_active_impersonation_is_harmless()
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('admin.impersonate.stop'))->assertRedirect(route('dashboard'));
        $this->assertSame($admin->id, Auth::id());
    }
}
