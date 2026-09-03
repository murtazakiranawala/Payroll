<?php

namespace Database\Seeders;

use App\Models\SalaryComponent;
use Illuminate\Database\Seeder;

class SalaryComponentSeeder extends Seeder
{
    public function run(): void
    {
        $earnings = [
            ['code' => 'HRA', 'name' => 'House Rent Allowance', 'calculation_type' => 'percent_of_basic', 'default_percentage' => 40, 'sort_order' => 1],
            ['code' => 'CCA', 'name' => 'City Compensatory Allowance', 'calculation_type' => 'percent_of_basic', 'default_percentage' => 60, 'sort_order' => 2],
            ['code' => 'CONVEYANCE', 'name' => 'Conveyance Allowance', 'calculation_type' => 'fixed', 'sort_order' => 3],
            ['code' => 'MEDICAL', 'name' => 'Medical Allowance', 'calculation_type' => 'fixed', 'sort_order' => 4],
            ['code' => 'SPECIAL', 'name' => 'Special Allowance', 'calculation_type' => 'fixed', 'sort_order' => 5],
            ['code' => 'TRANSPORT', 'name' => 'Transport Allowance', 'calculation_type' => 'fixed', 'sort_order' => 6],
            ['code' => 'OTHER_ALLOWANCE', 'name' => 'Other Allowances', 'calculation_type' => 'fixed', 'sort_order' => 7],
            ['code' => 'INCENTIVE', 'name' => 'Incentive / Bonus', 'calculation_type' => 'fixed', 'sort_order' => 8],
        ];

        foreach ($earnings as $earning) {
            SalaryComponent::updateOrCreate(['code' => $earning['code']], $earning + [
                'type' => 'earning',
                'is_statutory' => false,
                'is_active' => true,
            ]);
        }

        $statutoryDeductions = [
            ['code' => 'PF', 'name' => 'Provident Fund', 'statutory_type' => 'PF', 'sort_order' => 10],
            ['code' => 'ESI', 'name' => 'Employee State Insurance', 'statutory_type' => 'ESI', 'sort_order' => 11],
            ['code' => 'PT', 'name' => 'Professional Tax', 'statutory_type' => 'PT', 'sort_order' => 12],
            ['code' => 'TDS', 'name' => 'Tax Deducted at Source', 'statutory_type' => 'TDS', 'sort_order' => 13],
            ['code' => 'LWF', 'name' => 'Labour Welfare Fund', 'statutory_type' => 'LWF', 'sort_order' => 14],
        ];

        foreach ($statutoryDeductions as $deduction) {
            // Listed for reference/reporting only - these are computed automatically by
            // StatutoryComputationService, not selectable as salary-structure lines.
            SalaryComponent::updateOrCreate(['code' => $deduction['code']], $deduction + [
                'type' => 'deduction',
                'calculation_type' => 'fixed',
                'is_statutory' => true,
                'is_active' => true,
            ]);
        }
    }
}
