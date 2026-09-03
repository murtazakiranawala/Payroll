<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\School;
use App\Models\StatutoryRateConfig;
use App\Services\StatutoryComputationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatutoryComputationServiceTest extends TestCase
{
    use RefreshDatabase;

    private StatutoryComputationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StatutoryComputationService();
    }

    private function makeEmployee(): Employee
    {
        $school = School::create(['name' => 'Test School', 'code' => 'TS01']);

        // Explicit true/true/true/true rather than relying on the columns'
        // DB-level defaults: a freshly Model::create()'d instance never
        // re-fetches from the DB, so any attribute omitted here would read
        // back as null -> false through the boolean cast, not the DB's
        // actual default value.
        return Employee::create([
            'school_id' => $school->id,
            'name' => 'Test Employee',
            'employment_status' => 'active',
            'category' => 'other',
            'pf_applicable' => true,
            'esi_applicable' => true,
            'pt_applicable' => true,
            'lwf_applicable' => true,
        ]);
    }

    public function test_pf_is_computed_on_wage_ceiling(): void
    {
        StatutoryRateConfig::create([
            'type' => 'PF', 'name' => 'PF Test', 'effective_from' => '2024-01-01',
            'config' => ['employee_rate' => 12, 'employer_rate' => 12, 'wage_ceiling' => 15000],
        ]);

        $employee = $this->makeEmployee();

        // Basic above the ceiling should be capped at 15000 before applying the rate.
        $result = $this->service->calculatePf($employee, 20000, Carbon::create(2024, 5, 1));

        $this->assertEquals(1800.0, $result['employee']);
        $this->assertEquals(1800.0, $result['employer']);
    }

    public function test_pf_not_applicable_employee_has_zero_pf(): void
    {
        StatutoryRateConfig::create([
            'type' => 'PF', 'name' => 'PF Test', 'effective_from' => '2024-01-01',
            'config' => ['employee_rate' => 12, 'employer_rate' => 12, 'wage_ceiling' => 15000],
        ]);

        $employee = $this->makeEmployee();
        $employee->update(['pf_applicable' => false]);

        $result = $this->service->calculatePf($employee, 20000, Carbon::create(2024, 5, 1));

        $this->assertEquals(0.0, $result['employee']);
        $this->assertEquals(0.0, $result['employer']);
    }

    public function test_pt_uses_matching_slab(): void
    {
        StatutoryRateConfig::create([
            'type' => 'PT', 'name' => 'PT Test', 'effective_from' => '2024-01-01',
            'config' => ['slabs' => [
                ['min' => 0, 'max' => 7500, 'amount' => 0],
                ['min' => 7501, 'max' => 10000, 'amount' => 175],
                ['min' => 10001, 'max' => null, 'amount' => 200],
            ]],
        ]);

        $employee = $this->makeEmployee();

        $this->assertEquals(0.0, $this->service->calculatePt($employee, 5000, 0, 0, Carbon::create(2024, 5, 1)));
        $this->assertEquals(175.0, $this->service->calculatePt($employee, 9000, 0, 0, Carbon::create(2024, 5, 1)));
        $this->assertEquals(200.0, $this->service->calculatePt($employee, 50000, 0, 0, Carbon::create(2024, 5, 1)));
    }

    public function test_pt_wage_base_excludes_bonus_and_leave_encashment(): void
    {
        StatutoryRateConfig::create([
            'type' => 'PT', 'name' => 'PT Test', 'effective_from' => '2024-01-01',
            'config' => ['slabs' => [
                ['min' => 0, 'max' => 12000, 'amount' => 0],
                ['min' => 12001, 'max' => null, 'amount' => 200],
            ]],
        ]);

        $employee = $this->makeEmployee();

        // Gross 20000 alone would be over the 12000 threshold, but with a
        // 10000 bonus stripped out the PT wage base drops to 10000 (exempt).
        $this->assertEquals(0.0, $this->service->calculatePt($employee, 20000, 10000, 0, Carbon::create(2024, 5, 1)));
        $this->assertEquals(200.0, $this->service->calculatePt($employee, 20000, 0, 0, Carbon::create(2024, 5, 1)));
    }

    public function test_pf_returns_wage_base_and_edli(): void
    {
        StatutoryRateConfig::create([
            'type' => 'PF', 'name' => 'PF Test', 'effective_from' => '2024-01-01',
            'config' => ['employee_rate' => 12, 'employer_rate' => 12, 'wage_ceiling' => 15000, 'edli_rate' => 0.5],
        ]);

        $employee = $this->makeEmployee();

        $result = $this->service->calculatePf($employee, 20000, Carbon::create(2024, 5, 1));

        $this->assertEquals(15000.0, $result['wage_base']);
        $this->assertEquals(75.0, $result['employer_edli']);
    }

    public function test_pf_wage_override_is_used_instead_of_basic(): void
    {
        StatutoryRateConfig::create([
            'type' => 'PF', 'name' => 'PF Test', 'effective_from' => '2024-01-01',
            'config' => ['employee_rate' => 12, 'employer_rate' => 12, 'wage_ceiling' => 15000],
        ]);

        $employee = $this->makeEmployee();

        $result = $this->service->calculatePf($employee, 8000, Carbon::create(2024, 5, 1), wageOverride: 12000);

        $this->assertEquals(12000.0, $result['wage_base']);
        $this->assertEquals(1440.0, $result['employee']);
    }

    public function test_school_specific_rate_overrides_the_global_default(): void
    {
        $schoolWithOverride = School::create(['name' => 'Override School', 'code' => 'OS01']);
        $schoolWithoutOverride = School::create(['name' => 'Default School', 'code' => 'DS01']);

        StatutoryRateConfig::create([
            'school_id' => null, 'type' => 'PT', 'name' => 'Global PT', 'effective_from' => '2024-01-01',
            'config' => ['slabs' => [['min' => 0, 'max' => null, 'amount' => 200]]],
        ]);

        StatutoryRateConfig::create([
            'school_id' => $schoolWithOverride->id, 'type' => 'PT', 'name' => 'Override School PT', 'effective_from' => '2024-01-01',
            'config' => ['slabs' => [['min' => 0, 'max' => null, 'amount' => 50]]],
        ]);

        $employeeWithOverride = Employee::create([
            'school_id' => $schoolWithOverride->id, 'name' => 'A', 'employment_status' => 'active', 'category' => 'other',
            'pt_applicable' => true,
        ]);
        $employeeWithoutOverride = Employee::create([
            'school_id' => $schoolWithoutOverride->id, 'name' => 'B', 'employment_status' => 'active', 'category' => 'other',
            'pt_applicable' => true,
        ]);

        $this->assertEquals(50.0, $this->service->calculatePt($employeeWithOverride, 20000, 0, 0, Carbon::create(2024, 5, 1)));
        $this->assertEquals(200.0, $this->service->calculatePt($employeeWithoutOverride, 20000, 0, 0, Carbon::create(2024, 5, 1)));
    }
}
