<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\StatutoryRateConfig;
use Carbon\Carbon;

/**
 * BRD FR-2.2: PF/ESI/TDS/PT/LWF computed from configurable, rule-based
 * rates/slabs stored in statutory_rate_configs (globally or per-school -
 * see StatutoryRateConfig::activeFor()), honoring the employee master's
 * pf_applicable/esi_applicable/pt_applicable/lwf_applicable flags.
 *
 * TDS here is intentionally simplified (a single flat annual slab table
 * applied to annualized gross) rather than a full Income Tax computation
 * engine (Section 80C/80D investment declarations, HRA exemption workings,
 * old vs new regime, etc.) - flagged in the README as a follow-up, since it
 * was not itemized as a distinct requirement in the BRD beyond "compute TDS".
 */
class StatutoryComputationService
{
    /**
     * PF config JSON: {employee_rate, employer_rate, wage_ceiling, eps_rate?, edli_rate?}.
     * `employer` is the combined EPS+EPF-balance total (what the employer_rate
     * historically represented) - EPS/EPF-balance are derived from it plus
     * eps_rate at report time rather than stored separately. `employer_edli`
     * is genuinely additional employer-only cost, not part of `employer`.
     *
     * @return array{wage_base: float, employee: float, employer: float, employer_edli: float}
     */
    public function calculatePf(Employee $employee, float $basic, Carbon $asOf, ?float $wageOverride = null): array
    {
        if (! $employee->pf_applicable) {
            return ['wage_base' => 0.0, 'employee' => 0.0, 'employer' => 0.0, 'employer_edli' => 0.0];
        }

        $config = StatutoryRateConfig::activeFor($employee->school_id, 'PF', $asOf->toDateString());

        if (! $config) {
            return ['wage_base' => 0.0, 'employee' => 0.0, 'employer' => 0.0, 'employer_edli' => 0.0];
        }

        $cfg = $config->config;
        $wage = $wageOverride > 0 ? $wageOverride : $basic;

        if (! empty($cfg['wage_ceiling'])) {
            $wage = min($wage, (float) $cfg['wage_ceiling']);
        }

        return [
            'wage_base' => $wage,
            'employee' => round($wage * ((float) ($cfg['employee_rate'] ?? 12) / 100), 2),
            'employer' => round($wage * ((float) ($cfg['employer_rate'] ?? 12) / 100), 2),
            'employer_edli' => round($wage * ((float) ($cfg['edli_rate'] ?? 0) / 100), 2),
        ];
    }

    /** @return array{wage_base: float, employee: float, employer: float} */
    public function calculateEsi(Employee $employee, float $grossEarnings, Carbon $asOf, ?float $wageOverride = null): array
    {
        if (! $employee->esi_applicable) {
            return ['wage_base' => 0.0, 'employee' => 0.0, 'employer' => 0.0];
        }

        $config = StatutoryRateConfig::activeFor($employee->school_id, 'ESI', $asOf->toDateString());

        if (! $config) {
            return ['wage_base' => 0.0, 'employee' => 0.0, 'employer' => 0.0];
        }

        $cfg = $config->config;
        $wage = $wageOverride > 0 ? $wageOverride : $grossEarnings;

        // Coverage-ceiling crossing mid-contribution-period doesn't stop ESI
        // contributions under the real scheme - that's why this is a manual
        // esi_applicable judgment call rather than an automatic cutoff here.
        return [
            'wage_base' => $wage,
            'employee' => round($wage * ((float) ($cfg['employee_rate'] ?? 0.75) / 100), 2),
            'employer' => round($wage * ((float) ($cfg['employer_rate'] ?? 3.25) / 100), 2),
        ];
    }

    /** PT wage base excludes bonus and leave encashment, matching the standard state PT formulas. */
    public function calculatePt(Employee $employee, float $grossEarnings, float $bonusAmount, float $leaveEncashmentAmount, Carbon $asOf): float
    {
        if (! $employee->pt_applicable) {
            return 0.0;
        }

        $config = StatutoryRateConfig::activeFor($employee->school_id, 'PT', $asOf->toDateString());

        if (! $config) {
            return 0.0;
        }

        $ptWageBase = max($grossEarnings - $bonusAmount - $leaveEncashmentAmount, 0);

        foreach ($config->config['slabs'] ?? [] as $slab) {
            $min = (float) ($slab['min'] ?? 0);
            $max = $slab['max'] ?? null;

            if ($ptWageBase >= $min && ($max === null || $ptWageBase <= (float) $max)) {
                return (float) ($slab['amount'] ?? 0);
            }
        }

        return 0.0;
    }

    /** @return array{employee: float, employer: float} */
    public function calculateLwf(Employee $employee, Carbon $asOf): array
    {
        if (! $employee->lwf_applicable) {
            return ['employee' => 0.0, 'employer' => 0.0];
        }

        // No active LWF config for this date == not due this cycle (LWF is
        // typically half-yearly, not every month) - toggle by (de)activating
        // or date-windowing the config rather than a separate monthly flag.
        $config = StatutoryRateConfig::activeFor($employee->school_id, 'LWF', $asOf->toDateString());

        if (! $config) {
            return ['employee' => 0.0, 'employer' => 0.0];
        }

        return [
            'employee' => (float) ($config->config['employee_amount'] ?? 0),
            'employer' => (float) ($config->config['employer_amount'] ?? 0),
        ];
    }

    public function calculateTds(Employee $employee, float $annualizedGross, Carbon $asOf): float
    {
        $config = StatutoryRateConfig::activeFor($employee->school_id, 'TDS', $asOf->toDateString());

        if (! $config) {
            return 0.0;
        }

        $exemption = (float) ($config->config['annual_exemption'] ?? 0);
        $taxable = max($annualizedGross - $exemption, 0);
        $annualTax = 0.0;
        $previousMax = 0.0;

        foreach ($config->config['slabs'] ?? [] as $slab) {
            $max = $slab['max'] ?? null;
            $rate = (float) ($slab['rate'] ?? 0) / 100;
            $sliceTop = $max === null ? $taxable : min($taxable, (float) $max);

            if ($sliceTop > $previousMax) {
                $annualTax += ($sliceTop - $previousMax) * $rate;
                $previousMax = $sliceTop;
            }

            if ($max !== null && $taxable <= (float) $max) {
                break;
            }
        }

        return round($annualTax / 12, 2);
    }
}
