<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $school = School::factory()->create();
        $this->superAdmin = User::factory()->create(['current_school_id' => $school->id]);
        $this->superAdmin->assignRole('Super Admin');
        $this->superAdmin->schools()->attach($school->id);
    }

    public function test_super_admin_sees_roles_list_and_can_edit_a_role()
    {
        $this->actingAs($this->superAdmin)->get(route('admin.roles.index'))
            ->assertOk()->assertSee('Roles')->assertSee('Librarian')->assertSee('Teacher');

        $this->actingAs($this->superAdmin)->get(route('admin.roles.edit', Role::findByName('Librarian')))
            ->assertOk()->assertSee('students.view')->assertSee('Save permissions');
    }

    public function test_updating_a_role_changes_its_permissions_immediately()
    {
        $librarian = Role::findByName('Librarian');
        $this->assertSame(0, $librarian->permissions()->count());

        $this->actingAs($this->superAdmin)->put(route('admin.roles.update', $librarian), [
            'permissions' => ['students.view', 'homework.view'],
        ])->assertRedirect(route('admin.roles.index'))->assertSessionHas('success');

        $this->assertEqualsCanonicalizing(
            ['students.view', 'homework.view'],
            $librarian->fresh()->permissions->pluck('name')->all()
        );

        // Clearing all removes them
        $this->actingAs($this->superAdmin)->put(route('admin.roles.update', $librarian), []);
        $this->assertSame(0, $librarian->fresh()->permissions()->count());
    }

    public function test_super_admin_role_cannot_be_edited()
    {
        $this->actingAs($this->superAdmin)->get(route('admin.roles.edit', Role::findByName('Super Admin')))->assertForbidden();
        $this->actingAs($this->superAdmin)->put(route('admin.roles.update', Role::findByName('Super Admin')), ['permissions' => []])->assertForbidden();
    }

    public function test_invalid_permission_name_is_rejected()
    {
        $this->actingAs($this->superAdmin)
            ->put(route('admin.roles.update', Role::findByName('Teacher')), ['permissions' => ['not.a.real.permission']])
            ->assertSessionHasErrors('permissions.0');
    }

    public function test_non_super_admin_cannot_access_role_manager()
    {
        $school = School::factory()->create();
        $teacher = User::factory()->create(['current_school_id' => $school->id]);
        $teacher->assignRole('Teacher');
        $teacher->schools()->attach($school->id);

        $this->actingAs($teacher)->get(route('admin.roles.index'))->assertForbidden();
        $this->actingAs($teacher)->get(route('admin.roles.edit', Role::findByName('Librarian')))->assertForbidden();
        $this->actingAs($teacher)->put(route('admin.roles.update', Role::findByName('Librarian')), ['permissions' => ['students.view']])->assertForbidden();
    }
}
