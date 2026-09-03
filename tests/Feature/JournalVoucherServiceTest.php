<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\GlAccountMapping;
use App\Models\PayrollCycle;
use App\Models\PayrollItem;
use App\Models\School;
use App\Services\JournalVoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class JournalVoucherServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeApprovedCycleWithItems(): PayrollCycle
    {
        $school = School::create(['name' => 'Test School', 'code' => 'TS01']);
        $employee = Employee::create([
            'school_id' => $school->id, 'name' => 'Employee', 'employment_status' => 'active', 'category' => 'other',
        ]);

        $cycle = PayrollCycle::create(['school_id' => $school->id, 'month' => 6, 'year' => 2024, 'status' => 'approved']);

        PayrollItem::create([
            'payroll_cycle_id' => $cycle->id, 'employee_id' => $employee->id,
            'basic' => 20000, 'gross_earnings' => 28000, 'gross_deductions' => 1800,
            'pf_employee' => 1800, 'pf_employer' => 1800, 'net_pay' => 26200,
        ]);

        foreach (GlAccountMapping::CATEGORIES as $category) {
            GlAccountMapping::create(['school_id' => null, 'category' => $category, 'gl_account_code' => "GL-{$category}"]);
        }

        return $cycle;
    }

    public function test_building_twice_returns_the_same_voucher(): void
    {
        $cycle = $this->makeApprovedCycleWithItems();
        $service = app(JournalVoucherService::class);

        $first = $service->buildForCycle($cycle);
        $second = $service->buildForCycle($cycle);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, \App\Models\JournalVoucher::count());
    }

    public function test_voucher_debits_equal_credits(): void
    {
        $cycle = $this->makeApprovedCycleWithItems();
        $voucher = app(JournalVoucherService::class)->buildForCycle($cycle);

        $this->assertEquals($voucher->totalDebit(), $voucher->totalCredit());
    }

    public function test_voucher_balances_with_manual_earnings_and_deductions_present(): void
    {
        $school = School::create(['name' => 'Test School', 'code' => 'TS01']);
        $employee = Employee::create([
            'school_id' => $school->id, 'name' => 'Employee', 'employment_status' => 'active', 'category' => 'other',
        ]);

        $cycle = PayrollCycle::create(['school_id' => $school->id, 'month' => 6, 'year' => 2024, 'status' => 'approved']);

        PayrollItem::create([
            'payroll_cycle_id' => $cycle->id, 'employee_id' => $employee->id,
            'basic' => 20000, 'gross_earnings' => 28000,
            'ot_amount' => 500, 'bonus_amount' => 2000, 'arrears_amount' => 300, 'other_earnings_amount' => 100,
            'gross_deductions' => 1900, 'other_deduction_amount' => 100,
            'pf_employee' => 1800, 'pf_employer' => 1800, 'pf_employer_edli' => 75,
            'net_pay' => 29000, // (28000+500+2000+300+100 earnings) - (1800 pf + 100 other deduction)
        ]);

        foreach (GlAccountMapping::CATEGORIES as $category) {
            GlAccountMapping::create(['school_id' => null, 'category' => $category, 'gl_account_code' => "GL-{$category}"]);
        }

        $voucher = app(JournalVoucherService::class)->buildForCycle($cycle);

        $this->assertEquals($voucher->totalDebit(), $voucher->totalCredit());
    }

    public function test_posting_twice_is_rejected(): void
    {
        $cycle = $this->makeApprovedCycleWithItems();
        $service = app(JournalVoucherService::class);
        $voucher = $service->buildForCycle($cycle);

        $service->post($voucher);

        $this->expectException(RuntimeException::class);
        $service->post($voucher->fresh());
    }
}
