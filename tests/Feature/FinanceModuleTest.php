<?php

namespace Tests\Feature;

use App\Facades\Tenant;
use App\Models\Fee;
use App\Models\SalaryPayment;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $accountant;
    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->school = School::factory()->create(['name' => 'Green Valley School']);
        Tenant::setSchool($this->school);

        $this->accountant = User::factory()->create(['current_school_id' => $this->school->id]);
        $this->accountant->assignRole('Accountant');
        $this->accountant->schools()->attach($this->school->id);
    }

    private function student(string $adm = 'ADM-1'): Student
    {
        return Student::create([
            'school_id' => $this->school->id, 'admission_number' => $adm, 'first_name' => 'Riya', 'last_name' => 'Rao',
            'date_of_birth' => '2015-01-01', 'gender' => 'female', 'admission_date' => '2026-04-01', 'status' => 'active',
        ]);
    }

    /* ---------------- Fees ---------------- */

    public function test_accountant_can_create_collect_and_print_a_fee()
    {
        $student = $this->student();

        $this->actingAs($this->accountant)->post(route('fees.store'), [
            'student_id' => $student->id, 'title' => 'Tuition — November', 'amount' => 1000, 'discount' => 100, 'fine' => 50,
        ])->assertRedirect();

        $fee = Fee::firstOrFail();
        $this->assertSame('950.00', $fee->amount === null ? null : number_format($fee->payable, 2)); // 1000 + 50 - 100
        $this->assertSame('unpaid', $fee->status);

        // Partial payment
        $this->actingAs($this->accountant)->post(route('fees.collect', $fee), ['amount' => 400, 'paid_on' => '2026-11-10', 'method' => 'Cash'])
            ->assertRedirect(route('fees.show', $fee));
        $fee->refresh();
        $this->assertSame('partial', $fee->status);
        $this->assertEquals(550.00, $fee->balance);

        // Overpay is rejected
        $this->actingAs($this->accountant)->post(route('fees.collect', $fee), ['amount' => 9999, 'paid_on' => '2026-11-11'])
            ->assertSessionHasErrors('amount');

        // Pay the rest → paid
        $this->actingAs($this->accountant)->post(route('fees.collect', $fee), ['amount' => 550, 'paid_on' => '2026-11-12']);
        $this->assertSame('paid', $fee->fresh()->status);

        // Printable slip renders
        $this->actingAs($this->accountant)->get(route('fees.slip', $fee))
            ->assertOk()->assertSee('FEE RECEIPT')->assertSee('Green Valley School')->assertSee('Riya Rao');
    }

    public function test_fee_of_another_school_is_not_accessible()
    {
        $other = School::factory()->create();
        $foreignStudent = Student::create(['school_id' => $other->id, 'admission_number' => 'X-1', 'first_name' => 'A', 'last_name' => 'B',
            'date_of_birth' => '2015-01-01', 'gender' => 'male', 'admission_date' => '2026-04-01', 'status' => 'active']);
        $fee = Fee::create(['school_id' => $other->id, 'student_id' => $foreignStudent->id, 'title' => 'X', 'amount' => 100, 'status' => 'unpaid']);

        $this->actingAs($this->accountant)->get(route('fees.show', $fee))->assertNotFound();
    }

    public function test_student_cannot_access_fees()
    {
        $student = User::factory()->create(['current_school_id' => $this->school->id]);
        $student->assignRole('Student');
        $student->schools()->attach($this->school->id);

        $this->actingAs($student)->get(route('fees.index'))->assertForbidden();
        $this->actingAs($student)->get(route('fees.create'))->assertForbidden();
    }

    public function test_fee_report_totals_by_month_quarter_and_year()
    {
        $a = $this->student('ADM-A');
        $b = $this->student('ADM-B');

        // Year 2026
        Fee::create(['school_id' => $this->school->id, 'student_id' => $a->id, 'title' => 'Jan', 'amount' => 1000, 'paid_amount' => 1000, 'due_date' => '2026-01-15', 'status' => 'paid']);
        Fee::create(['school_id' => $this->school->id, 'student_id' => $a->id, 'title' => 'Apr', 'amount' => 500, 'paid_amount' => 200, 'due_date' => '2026-04-10', 'status' => 'partial']);
        Fee::create(['school_id' => $this->school->id, 'student_id' => $b->id, 'title' => 'Apr-B', 'amount' => 800, 'paid_amount' => 0, 'due_date' => '2026-04-20', 'status' => 'unpaid']);
        // Other year
        Fee::create(['school_id' => $this->school->id, 'student_id' => $a->id, 'title' => 'Prev', 'amount' => 300, 'paid_amount' => 0, 'due_date' => '2025-11-01', 'status' => 'unpaid']);

        $res = $this->actingAs($this->accountant)->get(route('fees.report', ['year' => 2026]));
        $res->assertOk();

        // Year 2026: billed 2300, collected 1200, outstanding 1100
        $res->assertSee('2,300.00')->assertSee('1,200.00')->assertSee('1,100.00');
        // A student that owes the most appears (B owes 800)
        $res->assertSee('Rao');
        // Other-year total is reachable from the "all years" table
        $res->assertSee('2025');
    }

    public function test_student_cannot_view_fee_report()
    {
        $student = User::factory()->create(['current_school_id' => $this->school->id]);
        $student->assignRole('Student');
        $student->schools()->attach($this->school->id);

        $this->actingAs($student)->get(route('fees.report'))->assertForbidden();
    }

    /* ---------------- Salary ---------------- */

    public function test_accountant_can_create_pay_and_print_a_payslip()
    {
        $this->actingAs($this->accountant)->post(route('salaries.store'), [
            'employee_name' => 'John Teacher', 'designation' => 'Senior Teacher', 'month' => '2026-11',
            'basic' => 30000, 'allowances' => 5000, 'deductions' => 2000,
        ])->assertRedirect();

        $salary = SalaryPayment::firstOrFail();
        $this->assertEquals(33000.00, $salary->net_salary); // 30000 + 5000 - 2000
        $this->assertSame('pending', $salary->status);

        $this->actingAs($this->accountant)->post(route('salaries.pay', $salary), ['paid_on' => '2026-11-30', 'method' => 'Bank Transfer'])
            ->assertRedirect(route('salaries.show', $salary));
        $this->assertSame('paid', $salary->fresh()->status);

        $this->actingAs($this->accountant)->get(route('salaries.slip', $salary))
            ->assertOk()->assertSee('PAYSLIP')->assertSee('John Teacher')->assertSee('November 2026');
    }

    public function test_teacher_without_salary_permission_is_blocked()
    {
        $teacher = User::factory()->create(['current_school_id' => $this->school->id]);
        $teacher->assignRole('Teacher');
        $teacher->schools()->attach($this->school->id);

        $this->actingAs($teacher)->get(route('salaries.index'))->assertForbidden();
        $this->actingAs($teacher)->post(route('salaries.store'), ['employee_name' => 'X', 'month' => '2026-11', 'basic' => 1])->assertForbidden();
    }

    public function test_superadmin_sees_finance_menu_and_pages()
    {
        $super = User::factory()->create(['current_school_id' => $this->school->id]);
        $super->assignRole('Super Admin');
        $super->schools()->attach($this->school->id);

        $this->actingAs($super)->get(route('dashboard'))->assertOk()->assertSee('Fees')->assertSee('Salaries');
        $this->actingAs($super)->get(route('fees.index'))->assertOk();
        $this->actingAs($super)->get(route('salaries.index'))->assertOk();
    }
}
