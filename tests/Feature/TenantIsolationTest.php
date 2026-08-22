<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;
use App\Facades\Tenant;
use Illuminate\Http\Request;
use App\Http\Middleware\EnsureTenantContext;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_user_cannot_access_other_school_data()
    {
        // Create School A and B
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();

        // Create Admin for School A
        $adminA = User::factory()->create(['current_school_id' => $schoolA->id]);
        $adminA->schools()->attach($schoolA);
        $adminA->assignRole('School Admin');

        // Create Academic Session for School B
        $sessionB = AcademicSession::factory()->create(['school_id' => $schoolB->id]);

        // Attempt to authorize viewing Session B by Admin A
        $this->actingAs($adminA);
        $this->assertFalse($adminA->can('view', $sessionB));
    }

    public function test_user_can_access_own_school_data()
    {
        $schoolA = School::factory()->create();
        
        $adminA = User::factory()->create(['current_school_id' => $schoolA->id]);
        $adminA->schools()->attach($schoolA);
        $adminA->assignRole('School Admin');

        $sessionA = AcademicSession::factory()->create(['school_id' => $schoolA->id]);

        $this->actingAs($adminA);
        $this->assertTrue($adminA->can('view', $sessionA));
    }

    public function test_super_admin_can_access_any_school()
    {
        $schoolB = School::factory()->create();

        $superAdmin = User::factory()->create();
        // Super Admin doesn't necessarily need to be attached to the school
        $superAdmin->assignRole('Super Admin');

        $sessionB = AcademicSession::factory()->create(['school_id' => $schoolB->id]);

        $this->actingAs($superAdmin);
        $this->assertTrue($superAdmin->can('view', $sessionB));
    }

    public function test_tenant_context_middleware_blocks_unauthorized_access()
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();

        // User A belongs to School A, but tries to set current_school_id to School B
        $userA = User::factory()->create(['current_school_id' => $schoolB->id]);
        $userA->schools()->attach($schoolA); // Only belongs to A
        $userA->assignRole('Teacher');
        
        $this->actingAs($userA);

        $request = Request::create('/some-route', 'GET');
        $request->setUserResolver(function () use ($userA) { return $userA; });
        $middleware = new EnsureTenantContext();

        try {
            $middleware->handle($request, function () { return response('OK'); });
            $this->fail('Expected HttpException 403');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
            $this->assertEquals('You do not have access to this school.', $e->getMessage());
        }
    }

    public function test_tenant_context_middleware_allows_super_admin()
    {
        $schoolB = School::factory()->create();

        // Super Admin sets current school to B (even though they aren't explicitly attached)
        $superAdmin = User::factory()->create(['current_school_id' => $schoolB->id]);
        $superAdmin->assignRole('Super Admin');
        
        $this->actingAs($superAdmin);

        $request = Request::create('/some-route', 'GET');
        $request->setUserResolver(function () use ($superAdmin) { return $superAdmin; });
        $middleware = new EnsureTenantContext();

        $response = $middleware->handle($request, function () {
            return response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
        $this->assertEquals($schoolB->id, Tenant::getSchoolId());
    }
}
