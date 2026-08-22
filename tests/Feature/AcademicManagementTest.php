<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AcademicManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_can_view_classes_if_authorized()
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['current_school_id' => $school->id]);
        $user->schools()->attach($school);
        $user->givePermissionTo('academic.manage');

        SchoolClass::factory()->create(['school_id' => $school->id, 'name' => 'Class 1']);

        $response = $this->actingAs($user)->get(route('academic.classes.index'));
        $response->assertStatus(200);
        $response->assertSee('Class 1');
    }

    public function test_cannot_view_classes_without_permission()
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['current_school_id' => $school->id]);
        $user->schools()->attach($school);

        $response = $this->actingAs($user)->get(route('academic.classes.index'));
        $response->assertStatus(403);
    }

    public function test_can_create_class()
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['current_school_id' => $school->id]);
        $user->schools()->attach($school);
        $user->givePermissionTo('academic.manage');

        $response = $this->actingAs($user)->post(route('academic.classes.store'), [
            'name' => 'Nursery',
            'level' => 1
        ]);

        $response->assertRedirect(route('academic.classes.index'));
        $this->assertDatabaseHas('school_classes', [
            'school_id' => $school->id,
            'name' => 'Nursery',
            'level' => 1
        ]);
    }

    public function test_cannot_see_other_school_classes()
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();

        $userA = User::factory()->create(['current_school_id' => $schoolA->id]);
        $userA->schools()->attach($schoolA);
        $userA->givePermissionTo('academic.manage');

        SchoolClass::factory()->create(['school_id' => $schoolA->id, 'name' => 'Class A']);
        SchoolClass::factory()->create(['school_id' => $schoolB->id, 'name' => 'Class B']);

        $response = $this->actingAs($userA)->get(route('academic.classes.index'));
        
        $response->assertStatus(200);
        $response->assertSee('Class A');
        $response->assertDontSee('Class B');
    }
}
