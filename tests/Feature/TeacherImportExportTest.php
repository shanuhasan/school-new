<?php

namespace Tests\Feature;

use App\Facades\Tenant;
use App\Models\Department;
use App\Models\Designation;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\TeacherImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeacherImportExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private School $school;

    private const HEADER = 'employee_id,first_name,last_name,email,phone,department,designation,joining_date,qualification,status,subjects,classes';

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create(['name' => 'Green Valley School']);
        Tenant::setSchool($this->school);

        Permission::firstOrCreate(['name' => 'staff.manage']);
        $role = Role::firstOrCreate(['name' => 'School Admin']);
        $role->givePermissionTo('staff.manage');

        $this->admin = User::factory()->create(['current_school_id' => $this->school->id]);
        $this->admin->assignRole($role);
        $this->admin->schools()->attach($this->school);

        Department::create(['school_id' => $this->school->id, 'name' => 'Science']);
        Designation::create(['school_id' => $this->school->id, 'name' => 'Senior Teacher']);
        Subject::create(['school_id' => $this->school->id, 'name' => 'Mathematics', 'code' => 'MATH']);
        Subject::create(['school_id' => $this->school->id, 'name' => 'Physics', 'code' => 'PHY']);
        SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Grade 5']);
        SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Grade 6']);
    }

    private function upload(array $lines, array $extra = [], ?User $as = null)
    {
        $file = UploadedFile::fake()->createWithContent('teachers.csv', self::HEADER . "\n" . implode("\n", $lines) . "\n");

        return $this->actingAs($as ?? $this->admin)->post(route('staff.teachers.import.store'), ['file' => $file] + $extra);
    }

    public function test_import_page_template_and_permissions()
    {
        $this->actingAs($this->admin)->get(route('staff.teachers.import.create'))
            ->assertOk()->assertSee('Import teachers')->assertSee('Mathematics (MATH)');

        $response = $this->actingAs($this->admin)->get(route('staff.teachers.import.template'));
        $response->assertOk();
        $this->assertStringContainsString(self::HEADER, $response->getContent());

        // A user without staff.manage can't import or export
        $plain = User::factory()->create(['current_school_id' => $this->school->id]);
        $plain->schools()->attach($this->school);
        $this->actingAs($plain)->get(route('staff.teachers.import.create'))->assertForbidden();
        $this->actingAs($plain)->get(route('staff.teachers.export'))->assertForbidden();
        $this->upload(['T-1,Asha,Rao,asha@school.test,,,,,,,,'], [], $plain)->assertForbidden();
        $this->assertSame(0, Teacher::count());
    }

    public function test_imports_teachers_with_accounts_subjects_and_classes()
    {
        $this->upload([
            'T-1,Asha,Rao,Asha@School.test,9000000001,science,senior teacher,01-06-2020,M.Sc,active,"Mathematics, PHY","Grade 5, grade 6"',
            'T-2,Vikram,Singh,vikram@school.test,,,,,,on leave,,',
        ])->assertRedirect(route('staff.teachers.import.create'));

        $this->assertSame(2, Teacher::count());
        $asha = Teacher::where('employee_id', 'T-1')->with(['user', 'subjects', 'classes', 'department', 'designation'])->first();
        $this->assertSame('Asha Rao', $asha->user->name);
        $this->assertSame('asha@school.test', $asha->user->email);
        $this->assertTrue($asha->user->hasRole('Teacher'));
        $this->assertTrue($asha->user->schools()->whereKey($this->school->id)->exists());
        $this->assertSame('Science', $asha->department->name);
        $this->assertSame('Senior Teacher', $asha->designation->name);
        $this->assertSame('2020-06-01', $asha->joining_date->toDateString());
        $this->assertSame(['Mathematics', 'Physics'], $asha->subjects->pluck('name')->sort()->values()->all());
        $this->assertSame(['Grade 5', 'Grade 6'], $asha->classes->pluck('name')->sort()->values()->all());
        $this->assertDatabaseHas('teachers', ['employee_id' => 'T-2', 'status' => 'on_leave']);
        $this->assertFalse(Hash::check('password', $asha->user->password), 'A random password is set');
    }

    public function test_errors_are_reported_and_nothing_saved()
    {
        User::factory()->create(['email' => 'taken@school.test']);
        Teacher::factory()->create(['school_id' => $this->school->id, 'employee_id' => 'T-9']);
        $before = Teacher::count();

        $this->upload([
            'T-1,Asha,Rao,asha@school.test,,,,,,,,',              // 2 ok
            'T-2,Ravi,Rao,TAKEN@school.test,,,,,,,,',             // 3 email in use
            'T-3,Ravi,Rao,asha@school.test,,,,,,,,',              // 4 repeated email
            'T-9,Old,Id,old@school.test,,,,,,,,',                 // 5 employee id exists
            'T-5,No,Dept,nodept@school.test,,Arts,Principal,31-31-2020,,retired,Chemistry,Grade 9', // 6
            'T-6,,Rao,,,,,,,,,',                                  // 7 missing first name + email
        ])->assertSessionHas('import_report');

        $this->assertSame($before, Teacher::count());
        $errors = session('import_report')['errors'];
        $this->assertSame([3, 4, 5, 6, 7], array_keys($errors));
        $this->assertStringContainsString('already belongs to an account', $errors[3][0]);
        $this->assertStringContainsString('repeated (also on row 2)', $errors[4][0]);
        $this->assertStringContainsString('employee_id T-9 already exists', $errors[5][0]);
        $row6 = implode(' ', $errors[6]);
        foreach (['31-31-2020', 'status must be', 'department "Arts"', 'designation "Principal"', 'subject "Chemistry"', 'class "Grade 9"'] as $needle) {
            $this->assertStringContainsString($needle, $row6);
        }
        $this->assertStringContainsString('first_name is required', implode(' ', $errors[7]));
        $this->assertStringContainsString('email is required', implode(' ', $errors[7]));
    }

    public function test_create_missing_departments_and_designations()
    {
        $this->upload([
            'T-1,Asha,Rao,asha@school.test,,Arts,Coordinator,,,,,',
            'T-2,Ravi,Rao,ravi@school.test,,arts,Coordinator,,,,,',
        ], ['create_missing' => 1]);

        $this->assertSame(2, Teacher::count());
        $this->assertSame(1, Department::where('name', 'Arts')->count(), 'Created once, reused');
        $this->assertSame(1, Designation::where('name', 'Coordinator')->count());
        $this->assertSame(['teachers' => 2, 'departments_created' => 1, 'designations_created' => 1], session('import_report')['imported']);
    }

    public function test_export_matches_template_respects_school_and_search()
    {
        $this->upload(['T-1,Asha,Rao,asha@school.test,09000000001,Science,Senior Teacher,2020-06-01,M.Sc,active,"MATH, Physics",Grade 5']);
        $this->upload(['T-2,Vikram,Singh,vikram@school.test,,,,,,,,']);

        // Teacher in another school must not appear
        $other = School::factory()->create();
        Teacher::factory()->create(['school_id' => $other->id, 'employee_id' => 'X-1']);

        $response = $this->actingAs($this->admin)->get(route('staff.teachers.export'));
        $response->assertOk();
        $this->assertStringContainsString('teachers-green-valley-school-', $response->headers->get('Content-Disposition'));

        $path = tempnam(sys_get_temp_dir(), 't') . '.xlsx';
        copy($response->baseResponse->getFile()->getPathname(), $path);
        $service = app(TeacherImportService::class);
        $rows = array_values($service->read(new UploadedFile($path, 't.xlsx', null, null, true)));

        $this->assertSame(['T-1', 'T-2'], array_column($rows, 'employee_id'));
        $this->assertSame('09000000001', $rows[0]['phone']);
        $this->assertSame('2020-06-01', \App\Support\SpreadsheetReader::parseDate($rows[0]['joining_date']));
        $this->assertSame('Mathematics, Physics', $rows[0]['subjects']);
        $this->assertSame('Science', $rows[0]['department']);

        // Filter by search
        $search = $this->actingAs($this->admin)->get(route('staff.teachers.export', ['search' => 'Vikram']));
        copy($search->baseResponse->getFile()->getPathname(), $path);
        $this->assertSame(['T-2'], array_values(array_column($service->read(new UploadedFile($path, 't.xlsx', null, null, true)), 'employee_id')));
    }

    public function test_teacher_search_does_not_bypass_department_filter()
    {
        $science = Department::where('name', 'Science')->first();
        $arts = Department::create(['school_id' => $this->school->id, 'name' => 'Arts']);
        // Both teachers match the name search; only one is in Science.
        // The old query (whereHas(...)->orWhere(...) without grouping) returned both.
        Teacher::factory()->create(['school_id' => $this->school->id, 'employee_id' => 'EMP-1', 'department_id' => $science->id,
            'user_id' => User::factory()->create(['name' => 'Anita Kumar'])->id]);
        Teacher::factory()->create(['school_id' => $this->school->id, 'employee_id' => 'EMP-2', 'department_id' => $arts->id,
            'user_id' => User::factory()->create(['name' => 'Rohit Kumar'])->id]);

        $this->actingAs($this->admin)
            ->get(route('staff.teachers.index', ['search' => 'Kumar', 'department_id' => $science->id]))
            ->assertOk()
            ->assertSee('EMP-1')
            ->assertDontSee('EMP-2');
    }
}
