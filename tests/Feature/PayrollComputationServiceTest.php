<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\PayrollCycle;
use App\Models\SalaryComponent;
use App\Models\School;
use App\Models\StatutoryRateConfig;
use App\Services\PayrollComputationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollComputationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_computes_net_pay_for_a_full_month(): void
    {
        $school = School::create(['name' => 'Test School', 'code' => 'TS01']);

        $employee = Employee::create([
            'school_id' => $school->id,
            'name' => 'Full Month Employee',
            'employment_status' => 'active',
            'category' => 'other',
            'date_of_joining' => '2020-01-01',
        ]);

        $structure = $employee->salaryStructures()->create([
            'effective_from' => '2020-01-01',
            'basic' => 20000,
            'is_active' => true,
        ]);

        $hra = SalaryComponent::create([
            'code' => 'HRA', 'name' => 'HRA', 'type' => 'earning',
            'calculation_type' => 'percent_of_basic', 'is_statutory' => false, 'is_active' => true,
        ]);
        $structure->lines()->create(['salary_component_id' => $hra->id, 'percentage' => 40]);

        StatutoryRateConfig::create([
            'type' => 'PF', 'name' => 'PF', 'effective_from' => '2020-01-01',
            'config' => ['employee_rate' => 12, 'employer_rate' => 12, 'wage_ceiling' => 15000],
        ]);

        $cycle = PayrollCycle::create([
            'school_id' => $school->id, 'month' => 6, 'year' => 2024, 'status' => 'draft',
        ]);

        $result = app(PayrollComputationService::class)->runCycle($cycle);

        $this->assertSame(1, $result['processed']);
        $this->assertEmpty($result['skipped']);

        $item = $cycle->items()->first();

        // Basic 20000 + HRA 40% of 20000 (8000) = 28000 gross; PF on basic capped at 15000 = 1800.
        $this->assertEquals(20000, $item->basic);
        $this->assertEquals(28000, $item->gross_earnings);
        $this->assertEquals(1800, $item->pf_employee);
        $this->assertEquals(28000 - 1800, $item->net_pay);
    }

    public function test_recompute_is_idempotent_while_draft(): void
    {
        $school = School::create(['name' => 'Test School', 'code' => 'TS01']);
        $employee = Employee::create([
            'school_id' => $school->id, 'name' => 'Employee', 'employment_status' => 'active',
            'category' => 'other', 'date_of_joining' => '2020-01-01',
        ]);
        $employee->salaryStructures()->create(['effective_from' => '2020-01-01', 'basic' => 10000, 'is_active' => true]);

        $cycle = PayrollCycle::create(['school_id' => $school->id, 'month' => 6, 'year' => 2024, 'status' => 'draft']);

        $service = app(PayrollComputationService::class);
        $service->runCycle($cycle);
        $service->runCycle($cycle);

        $this->assertSame(1, $cycle->items()->count());
    }

    public function test_manual_fields_survive_a_recompute(): void
    {
        $school = School::create(['name' => 'Test School', 'code' => 'TS01']);
        $employee = Employee::create([
            'school_id' => $school->id, 'name' => 'Employee', 'employment_status' => 'active',
            'category' => 'other', 'date_of_joining' => '2020-01-01',
        ]);
        $employee->salaryStructures()->create(['effective_from' => '2020-01-01', 'basic' => 10000, 'is_active' => true]);

        $cycle = PayrollCycle::create(['school_id' => $school->id, 'month' => 6, 'year' => 2024, 'status' => 'draft']);

        $service = app(PayrollComputationService::class);
        $service->runCycle($cycle);

        $item = $cycle->items()->first();
        $item->update(['bonus_amount' => 5000, 'manual_lop_days' => 2]);

        $service->runCycle($cycle);

        // Recompute deletes and recreates every item (the old row/ID is
        // gone even though its manual values carried over), so re-fetch.
        $item = $cycle->items()->first();
        $this->assertEquals(5000, $item->bonus_amount);
        $this->assertEquals(2, $item->manual_lop_days);
        // Bonus is added straight into net pay regardless of proration.
        $this->assertEquals(round(10000 * (28 / 30), 2) + 5000, $item->net_pay);
    }

    public function test_pf_wage_override_is_applied_during_computation(): void
    {
        $school = School::create(['name' => 'Test School', 'code' => 'TS01']);
        $employee = Employee::create([
            'school_id' => $school->id, 'name' => 'Employee', 'employment_status' => 'active',
            'category' => 'other', 'date_of_joining' => '2020-01-01',
        ]);
        $employee->salaryStructures()->create(['effective_from' => '2020-01-01', 'basic' => 8000, 'is_active' => true]);

        StatutoryRateConfig::create([
            'type' => 'PF', 'name' => 'PF', 'effective_from' => '2020-01-01',
            'config' => ['employee_rate' => 12, 'employer_rate' => 12, 'wage_ceiling' => 15000],
        ]);

        $cycle = PayrollCycle::create(['school_id' => $school->id, 'month' => 6, 'year' => 2024, 'status' => 'draft']);
        $service = app(PayrollComputationService::class);
        $service->runCycle($cycle);

        $item = $cycle->items()->first();
        $item->update(['pf_wage_override' => 12000]);
        $service->runCycle($cycle);

        $item = $cycle->items()->first();
        $this->assertEquals(12000, $item->pf_wages);
        $this->assertEquals(1440, $item->pf_employee);
    }
}
