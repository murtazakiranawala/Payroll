<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\FnfSettlement;
use App\Models\PayrollCycle;
use App\Models\PayrollItem;
use App\Models\PayrollItemComponent;
use App\Models\ReimbursementClaim;
use Illuminate\Support\Facades\DB;

/**
 * BRD FR-2.1/FR-2.3: computes each employee's monthly salary from their
 * approved salary structure, statutory deductions, and approved
 * reimbursement claims. Safe to re-run while the cycle is still "draft"
 * (rebuilds payroll_items, but preserves any F&F settlement data and any
 * HR-entered manual fields - loss-of-pay days, OT/bonus/arrears, wage
 * overrides, attendance detail - already recorded for this cycle).
 */
class PayrollComputationService
{
    /** Manual fields HR can enter per item that must survive a recompute. */
    private const PRESERVED_MANUAL_FIELDS = [
        'manual_lop_days', 'present_days', 'paid_leave_days', 'weekly_off_days', 'ot_hours',
        'ot_amount', 'bonus_amount', 'arrears_amount', 'other_earnings_amount', 'other_deduction_amount',
        'pf_wage_override', 'esi_wage_override',
    ];

    public function __construct(
        private StatutoryComputationService $statutory,
    ) {
    }

    /** @return array{processed: int, skipped: array<int, string>} */
    public function runCycle(PayrollCycle $cycle): array
    {
        if ($cycle->status !== 'draft') {
            throw new \RuntimeException('Payroll can only be (re)computed while the cycle is in draft status.');
        }

        $periodStart = $cycle->periodStart();
        $periodEnd = $cycle->periodEnd();
        $totalDays = $periodStart->daysInMonth;

        return DB::transaction(function () use ($cycle, $periodStart, $periodEnd, $totalDays) {
            $existingFnf = FnfSettlement::where('payroll_cycle_id', $cycle->id)->get()->keyBy('employee_id');
            $existingItems = PayrollItem::where('payroll_cycle_id', $cycle->id)->get()->keyBy('employee_id');

            $cycle->items()->delete();

            $employees = Employee::where('school_id', $cycle->school_id)
                ->where(function ($q) use ($periodEnd) {
                    $q->whereNull('date_of_joining')->orWhere('date_of_joining', '<=', $periodEnd);
                })
                ->where(function ($q) use ($periodStart) {
                    $q->whereNull('date_of_exit')->orWhere('date_of_exit', '>=', $periodStart);
                })
                ->get();

            $processed = 0;
            $skipped = [];

            foreach ($employees as $employee) {
                $structure = $employee->activeSalaryStructure($periodEnd->toDateString());

                if (! $structure) {
                    $skipped[] = $employee->name;

                    continue;
                }

                $previous = $existingItems->get($employee->id);
                $manual = [];

                foreach (self::PRESERVED_MANUAL_FIELDS as $field) {
                    $manual[$field] = $previous ? (float) $previous->$field : 0.0;
                }

                $basicFull = (float) $structure->basic;
                $earningLines = [];
                $grossEarningsFull = $basicFull;

                foreach ($structure->lines as $line) {
                    if ($line->component->isEarning()) {
                        $amount = $line->resolveAmount($basicFull);
                        $earningLines[] = ['component_id' => $line->salary_component_id, 'amount' => $amount];
                        $grossEarningsFull += $amount;
                    }
                }

                $employedFrom = $employee->date_of_joining && $employee->date_of_joining->gt($periodStart)
                    ? $employee->date_of_joining
                    : $periodStart;

                $employedTo = $employee->date_of_exit && $employee->date_of_exit->lt($periodEnd)
                    ? $employee->date_of_exit
                    : $periodEnd;

                // Diff on day-aligned copies: diffing against periodEnd's
                // 23:59:59.999999 (from endOfMonth()) makes Carbon's default
                // diffInDays() round a near-whole-month span up by one day.
                $employedDays = $employedTo->greaterThanOrEqualTo($employedFrom)
                    ? $employedFrom->copy()->startOfDay()->diffInDays($employedTo->copy()->startOfDay()) + 1
                    : 0;

                $payableDays = min(max($employedDays - $manual['manual_lop_days'], 0), $totalDays);
                $ratio = $totalDays > 0 ? min($payableDays / $totalDays, 1) : 0;

                $basicProrated = round($basicFull * $ratio, 2);
                $grossEarningsProrated = round($grossEarningsFull * $ratio, 2);

                $isExited = $employee->date_of_exit
                    && $employee->date_of_exit->between($periodStart, $periodEnd);

                $existingFnfForEmployee = $existingFnf->get($employee->id);
                $leaveEncashmentAmount = ($isExited && $existingFnfForEmployee?->finalized)
                    ? (float) $existingFnfForEmployee->leave_encashment_amount
                    : 0.0;

                $manualEarnings = $manual['ot_amount'] + $manual['bonus_amount'] + $manual['arrears_amount'] + $manual['other_earnings_amount'];

                $pf = $this->statutory->calculatePf($employee, $basicProrated, $periodEnd, $manual['pf_wage_override'] ?: null);
                $esi = $this->statutory->calculateEsi($employee, $grossEarningsProrated, $periodEnd, $manual['esi_wage_override'] ?: null);
                $pt = $this->statutory->calculatePt($employee, $grossEarningsProrated + $manualEarnings + $leaveEncashmentAmount, $manual['bonus_amount'], $leaveEncashmentAmount, $periodEnd);
                $lwf = $this->statutory->calculateLwf($employee, $periodEnd);
                $tds = $this->statutory->calculateTds($employee, $grossEarningsFull * 12, $periodEnd);

                $reimbursements = ReimbursementClaim::where('employee_id', $employee->id)
                    ->where('status', 'approved')
                    ->where('claim_date', '>=', $periodStart)
                    ->where('claim_date', '<=', $periodEnd)
                    ->where(function ($q) use ($cycle) {
                        $q->whereNull('payroll_cycle_id')->orWhere('payroll_cycle_id', $cycle->id);
                    })
                    ->get();

                $reimbursementsTotal = (float) $reimbursements->sum('amount');
                ReimbursementClaim::whereIn('id', $reimbursements->pluck('id'))
                    ->update(['payroll_cycle_id' => $cycle->id]);

                $grossDeductions = $pf['employee'] + $esi['employee'] + $tds + $pt + $lwf['employee'] + $manual['other_deduction_amount'];
                $netPay = round($grossEarningsProrated + $manualEarnings - $grossDeductions + $reimbursementsTotal, 2);

                $item = PayrollItem::create([
                    'payroll_cycle_id' => $cycle->id,
                    'employee_id' => $employee->id,
                    'total_days' => $totalDays,
                    'payable_days' => $payableDays,
                    'manual_lop_days' => $manual['manual_lop_days'],
                    'present_days' => $previous?->present_days,
                    'paid_leave_days' => $previous?->paid_leave_days,
                    'weekly_off_days' => $previous?->weekly_off_days,
                    'ot_hours' => $previous?->ot_hours,
                    'basic' => $basicProrated,
                    'gross_earnings' => $grossEarningsProrated,
                    'ot_amount' => $manual['ot_amount'],
                    'bonus_amount' => $manual['bonus_amount'],
                    'arrears_amount' => $manual['arrears_amount'],
                    'other_earnings_amount' => $manual['other_earnings_amount'],
                    'gross_deductions' => $grossDeductions,
                    'other_deduction_amount' => $manual['other_deduction_amount'],
                    'reimbursements_total' => $reimbursementsTotal,
                    'pf_employee' => $pf['employee'],
                    'pf_employer' => $pf['employer'],
                    'pf_employer_edli' => $pf['employer_edli'],
                    'pf_wages' => $pf['wage_base'],
                    'pf_wage_override' => $manual['pf_wage_override'] ?: null,
                    'esi_employee' => $esi['employee'],
                    'esi_employer' => $esi['employer'],
                    'esi_wages' => $esi['wage_base'],
                    'esi_wage_override' => $manual['esi_wage_override'] ?: null,
                    'tds' => $tds,
                    'pt' => $pt,
                    'lwf_employee' => $lwf['employee'],
                    'lwf_employer' => $lwf['employer'],
                    'is_fnf' => $isExited,
                    'net_pay' => $netPay,
                ]);

                // Basic itself lives on payroll_items.basic (set above); components
                // here cover the additional earning lines from the salary structure
                // (HRA, allowances, ...) for the payslip breakup.
                foreach ($earningLines as $line) {
                    PayrollItemComponent::create([
                        'payroll_item_id' => $item->id,
                        'salary_component_id' => $line['component_id'],
                        'type' => 'earning',
                        'amount' => round($line['amount'] * $ratio, 2),
                    ]);
                }

                if ($isExited) {
                    $completedYears = $employee->date_of_joining
                        ? $employee->date_of_joining->diffInYears($employee->date_of_exit)
                        : 0;

                    $fnf = $existingFnfForEmployee ?? new FnfSettlement([
                        'employee_id' => $employee->id,
                        'payroll_cycle_id' => $cycle->id,
                    ]);

                    $fnf->fill([
                        'payroll_item_id' => $item->id,
                        'exit_date' => $employee->date_of_exit,
                        'completed_years_of_service' => $completedYears,
                        'gratuity_eligible' => $completedYears >= 5,
                    ]);
                    $fnf->save();

                    if ($fnf->finalized) {
                        $item->update([
                            'fnf_amount' => $fnf->net_fnf_amount,
                            'net_pay' => round($netPay + (float) $fnf->net_fnf_amount, 2),
                        ]);
                    }
                }

                $processed++;
            }

            $cycle->update(['computed_at' => now()]);

            return ['processed' => $processed, 'skipped' => $skipped];
        });
    }
}
