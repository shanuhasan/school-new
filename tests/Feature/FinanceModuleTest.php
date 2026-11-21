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

    public function test_bulk_collect_and_bulk_waive_multiple_months()
    {
        $student = $this->student();
        // 4 monthly installments
        $fees = collect(['Apr', 'May', 'Jun', 'Jul'])->map(fn ($m, $i) => Fee::create([
            'school_id' => $this->school->id, 'student_id' => $student->id, 'title' => 'Tuition', 'period' => $m,
            'amount' => 1000, 'status' => 'unpaid', 'due_date' => '2026-0' . ($i + 4) . '-10',
        ]));

        // Advance payment: collect first 3 months at once
        $this->actingAs($this->accountant)->post(route('fees.bulk-collect'), [
            'fee_ids' => [$fees[0]->id, $fees[1]->id, $fees[2]->id], 'paid_on' => '2026-04-05', 'method' => 'Cash',
        ])->assertRedirect()->assertSessionHas('success');

        foreach ([0, 1, 2] as $i) {
            $this->assertSame('paid', $fees[$i]->fresh()->status);
            $this->assertEquals(0.00, $fees[$i]->fresh()->balance);
        }
        // 4th still unpaid
        $this->assertSame('unpaid', $fees[3]->fresh()->status);

        // Waive the remaining month in bulk
        $this->actingAs($this->accountant)->post(route('fees.bulk-waive'), [
            'fee_ids' => [$fees[3]->id], 'reason' => 'Final month concession',
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertSame('waived', $fees[3]->fresh()->status);

        // Student now owes nothing
        $this->assertEquals(0.00, $student->fees()->get()->sum(fn ($f) => $f->balance));
    }

    public function test_bulk_actions_respect_permissions_and_require_selection()
    {
        $student = $this->student();
        $fee = Fee::create(['school_id' => $this->school->id, 'student_id' => $student->id, 'title' => 'T', 'amount' => 100, 'status' => 'unpaid']);

        // No rows selected → validation error
        $this->actingAs($this->accountant)->post(route('fees.bulk-collect'), ['paid_on' => '2026-04-05'])->assertSessionHasErrors('fee_ids');
        // Waive needs a reason
        $this->actingAs($this->accountant)->post(route('fees.bulk-waive'), ['fee_ids' => [$fee->id]])->assertSessionHasErrors('reason');

        // A teacher (no fee permissions) is blocked
        $teacher = User::factory()->create(['current_school_id' => $this->school->id]);
        $teacher->assignRole('Teacher');
        $teacher->schools()->attach($this->school->id);
        $this->actingAs($teacher)->post(route('fees.bulk-collect'), ['fee_ids' => [$fee->id], 'paid_on' => '2026-04-05'])->assertForbidden();

        $this->assertEquals(0.00, $fee->fresh()->paid_amount);
    }

    public function test_fee_can_be_waived_fully_and_partially()
    {
        $student = $this->student();
        $fee = Fee::create(['school_id' => $this->school->id, 'student_id' => $student->id, 'title' => 'Tuition', 'amount' => 1000, 'status' => 'unpaid']);

        // Partial waive: 300 off → still owes 700, status partial
        $this->actingAs($this->accountant)->post(route('fees.waive', $fee), ['amount' => 300, 'reason' => 'Sibling concession'])
            ->assertRedirect(route('fees.show', $fee));
        $fee->refresh();
        $this->assertEquals(300.00, $fee->waived_amount);
        $this->assertEquals(700.00, $fee->balance);
        $this->assertSame('partial', $fee->status);
        $this->assertStringContainsString('Sibling concession', $fee->waive_reason);

        // Waive the rest → fully waived
        $this->actingAs($this->accountant)->post(route('fees.waive', $fee), ['amount' => 700, 'reason' => 'Scholarship']);
        $fee->refresh();
        $this->assertEquals(0.00, $fee->balance);
        $this->assertSame('waived', $fee->status);

        // Can't waive more than the balance, and reason is required
        $fee2 = Fee::create(['school_id' => $this->school->id, 'student_id' => $student->id, 'title' => 'Exam', 'amount' => 500, 'status' => 'unpaid']);
        $this->actingAs($this->accountant)->post(route('fees.waive', $fee2), ['amount' => 9999, 'reason' => 'x'])->assertSessionHasErrors('amount');
        $this->actingAs($this->accountant)->post(route('fees.waive', $fee2), ['amount' => 100])->assertSessionHasErrors('reason');

        // The waived amount shows on the printable slip
        $this->actingAs($this->accountant)->get(route('fees.slip', $fee))->assertOk()->assertSee('Waived')->assertSee('Scholarship');
    }

    public function test_student_fees_list_reflects_waived_amount_in_balance()
    {
        $student = $this->student();
        $fee = Fee::create(['school_id' => $this->school->id, 'student_id' => $student->id, 'title' => 'Tuition', 'amount' => 1000, 'status' => 'unpaid']);

        // Before waiving: balance shows full 1,000
        $this->actingAs($this->accountant)->get(route('fees.students'))->assertOk()->assertSee('1,000.00');

        // Waive 400 → balance should become 600 and a "Waived" marker appears
        $this->actingAs($this->accountant)->post(route('fees.waive', $fee), ['amount' => 400, 'reason' => 'Concession']);

        $this->actingAs($this->accountant)->get(route('fees.students'))
            ->assertOk()
            ->assertSee('Waived')     // badge + column
            ->assertSee('600.00')     // remaining balance
            ->assertSee('400.00');    // waived amount
    }

    public function test_waiving_requires_refund_permission()
    {
        $student = $this->student();
        $fee = Fee::create(['school_id' => $this->school->id, 'student_id' => $student->id, 'title' => 'T', 'amount' => 100, 'status' => 'unpaid']);

        // Librarian has no fee permissions
        $lib = User::factory()->create(['current_school_id' => $this->school->id]);
        $lib->assignRole('Librarian');
        $lib->schools()->attach($this->school->id);

        $this->actingAs($lib)->post(route('fees.waive', $fee), ['amount' => 50, 'reason' => 'x'])->assertForbidden();
        $this->assertEquals(0.00, $fee->fresh()->waived_amount);
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

    public function test_class_wise_fee_structure_generates_monthly_installments()
    {
        $session = \App\Models\AcademicSession::create([
            'school_id' => $this->school->id, 'name' => '2026-27',
            'start_date' => '2026-04-01', 'end_date' => '2027-03-31', 'is_current' => true,
        ]);
        $class = \App\Models\SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class I']);
        $section = \App\Models\Section::create(['school_class_id' => $class->id, 'name' => 'A']);

        // Two students enrolled in the class
        foreach (['ADM-1', 'ADM-2'] as $adm) {
            $student = $this->student($adm);
            $student->enrollments()->create([
                'school_id' => $this->school->id, 'academic_session_id' => $session->id,
                'school_class_id' => $class->id, 'section_id' => $section->id, 'status' => 'active',
            ]);
        }

        // Define a monthly tuition fee for the class
        $this->actingAs($this->accountant)->post(route('fees.structures.store'), [
            'academic_session_id' => $session->id, 'school_class_id' => $class->id,
            'title' => 'Tuition Fee', 'amount' => 1000, 'frequency' => 'monthly',
        ])->assertRedirect();

        $structure = \App\Models\FeeStructure::firstOrFail();
        $this->assertSame(12, $structure->periodCount());

        // Generate installments
        $this->actingAs($this->accountant)->post(route('fees.structures.generate', $structure))->assertRedirect();

        // 2 students × 12 months = 24 fee rows, each 1000, tagged with period + class
        $this->assertSame(24, Fee::where('fee_structure_id', $structure->id)->count());
        $apr = Fee::where('fee_structure_id', $structure->id)->where('period', 'April 2026')->first();
        $this->assertNotNull($apr);
        $this->assertEquals(1000.00, $apr->amount);
        $this->assertSame($class->id, $apr->school_class_id);

        // Re-generating does not duplicate
        $this->actingAs($this->accountant)->post(route('fees.structures.generate', $structure));
        $this->assertSame(24, Fee::where('fee_structure_id', $structure->id)->count());

        // Ledger shows the student's 12 periods, paid/unpaid tracked
        $student = Student::where('admission_number', 'ADM-1')->first();
        $this->actingAs($this->accountant)->get(route('fees.student', $student))
            ->assertOk()->assertSee('April 2026')->assertSee('March 2027')->assertSee('Tuition Fee');

        // Pay one month → that period becomes paid
        $aprOfStudent = $student->fees()->where('period', 'April 2026')->first();
        $this->actingAs($this->accountant)->post(route('fees.collect', $aprOfStudent), ['amount' => 1000, 'paid_on' => '2026-04-05']);
        $this->assertSame('paid', $aprOfStudent->fresh()->status);

        // Student fees list shows a balance of 11,000 remaining (12k billed - 1k paid)
        $this->actingAs($this->accountant)->get(route('fees.students'))->assertOk()->assertSee('11,000.00');
    }

    public function test_quarterly_and_yearly_period_counts()
    {
        $session = \App\Models\AcademicSession::create(['school_id' => $this->school->id, 'name' => '2026-27', 'start_date' => '2026-04-01', 'end_date' => '2027-03-31']);
        $class = \App\Models\SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class II']);

        $q = \App\Models\FeeStructure::create(['school_id' => $this->school->id, 'academic_session_id' => $session->id, 'school_class_id' => $class->id, 'title' => 'T', 'amount' => 3000, 'frequency' => 'quarterly']);
        $y = \App\Models\FeeStructure::create(['school_id' => $this->school->id, 'academic_session_id' => $session->id, 'school_class_id' => $class->id, 'title' => 'Y', 'amount' => 12000, 'frequency' => 'yearly']);

        $this->assertSame(4, $q->periodCount());
        $this->assertSame(1, $y->periodCount());
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

        $this->actingAs($super)->get(route('dashboard'))->assertOk()->assertSee('Student fees')->assertSee('Class fees')->assertSee('Salaries');
        $this->actingAs($super)->get(route('fees.index'))->assertOk();
        $this->actingAs($super)->get(route('salaries.index'))->assertOk();
    }
}
