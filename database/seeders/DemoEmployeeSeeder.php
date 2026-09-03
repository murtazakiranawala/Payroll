<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\School;
use App\Services\EmployeeSyncService;
use Illuminate\Database\Seeder;

/**
 * The org's 50-school roster (SchoolSeeder) is real, so this seeder does NOT
 * auto-attach fictional AIIMS-mock employees to all of them. Instead it runs
 * a full mock sync + sets up basic salary structures for just two sample
 * schools, purely so the full payroll cycle can be demoed immediately after
 * `migrate --seed`. Every other school stays clean until someone actually
 * syncs it from AIIMS (or adds employees manually) via the Employees screen.
 */
class DemoEmployeeSeeder extends Seeder
{
    private const DEMO_SCHOOLS = ['JEMS Dholka', 'SHSS Khergone'];

    public function run(EmployeeSyncService $syncService): void
    {
        $demoSchoolCodes = array_map([SchoolSeeder::class, 'codeFor'], self::DEMO_SCHOOLS);

        $schools = School::whereIn('code', $demoSchoolCodes)->get();

        foreach ($schools as $school) {
            $syncService->syncSchool($school, full: true, runType: 'manual');
        }

        $hra = \App\Models\SalaryComponent::where('code', 'HRA')->first();
        $conveyance = \App\Models\SalaryComponent::where('code', 'CONVEYANCE')->first();

        Employee::whereDoesntHave('salaryStructures')->each(function (Employee $employee) use ($hra, $conveyance) {
            $basic = 20000 + ($employee->id % 5) * 2000;

            $structure = $employee->salaryStructures()->create([
                'effective_from' => $employee->date_of_joining ?? now()->subYear(),
                'ctc' => $basic * 12 * 1.6,
                'basic' => $basic,
                'is_active' => true,
            ]);

            if ($hra) {
                $structure->lines()->create(['salary_component_id' => $hra->id, 'percentage' => 40]);
            }

            if ($conveyance) {
                $structure->lines()->create(['salary_component_id' => $conveyance->id, 'amount' => 1600]);
            }
        });
    }
}
