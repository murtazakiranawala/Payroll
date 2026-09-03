<?php

namespace Database\Seeders;

use App\Models\StatutoryRateConfig;
use Illuminate\Database\Seeder;

/**
 * Global (school_id null) defaults, sourced from the org's reference Excel
 * (Detailed_Salary_Register_India_Gujarat.xlsx "Statutory Settings" sheet -
 * see the cited government sources there). PT/LWF in particular are
 * Gujarat-specific; schools in other states should get their own
 * school-scoped override added under Statutory Rates once their state's
 * rules are confirmed.
 */
class StatutoryRateConfigSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            [
                'type' => 'PF', 'name' => 'PF - Standard (12%/12%, EPS 8.33%, EDLI 0.5%, ceiling 15000)',
                'config' => [
                    'employee_rate' => 12, 'employer_rate' => 12, 'wage_ceiling' => 15000,
                    'eps_rate' => 8.33, 'edli_rate' => 0.5,
                ],
            ],
            [
                'type' => 'ESI', 'name' => 'ESI - Standard (0.75%/3.25%, wage ceiling 21000)',
                'config' => ['employee_rate' => 0.75, 'employer_rate' => 3.25, 'wage_ceiling' => 21000],
            ],
            [
                'type' => 'PT', 'name' => 'Professional Tax - Gujarat',
                'config' => ['slabs' => [
                    ['min' => 0, 'max' => 12000, 'amount' => 0],
                    ['min' => 12001, 'max' => null, 'amount' => 200],
                ]],
            ],
            [
                // LWF is typically collected only in specific half-yearly
                // months, not every month - seeded here as always-active for
                // demo simplicity. To match Gujarat's actual collection
                // calendar, narrow this row's effective_from/effective_to to
                // just the due month(s) (an inactive/out-of-window LWF config
                // is exactly what "not due this month" means to the system -
                // see StatutoryComputationService::calculateLwf).
                'type' => 'LWF', 'name' => 'Labour Welfare Fund - Gujarat',
                'config' => ['employee_amount' => 6, 'employer_amount' => 12],
            ],
            [
                'type' => 'TDS', 'name' => 'TDS - Sample Annual Slabs (New Regime style)',
                'config' => [
                    'annual_exemption' => 300000,
                    'slabs' => [
                        ['max' => 300000, 'rate' => 0],
                        ['max' => 600000, 'rate' => 5],
                        ['max' => 900000, 'rate' => 10],
                        ['max' => 1200000, 'rate' => 15],
                        ['max' => 1500000, 'rate' => 20],
                        ['max' => null, 'rate' => 30],
                    ],
                ],
            ],
        ];

        foreach ($configs as $config) {
            StatutoryRateConfig::updateOrCreate(
                ['school_id' => null, 'type' => $config['type'], 'effective_from' => '2024-04-01'],
                $config + ['effective_from' => '2024-04-01', 'effective_to' => null, 'is_active' => true]
            );
        }
    }
}
