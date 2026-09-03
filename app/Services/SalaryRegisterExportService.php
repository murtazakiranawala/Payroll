<?php

namespace App\Services;

use App\Concerns\AddsExcelLogo;
use App\Models\Employee;
use App\Models\PayrollCycle;
use App\Models\PayrollItem;
use App\Models\StatutoryRateConfig;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Reproduces the "Detailed Salary Register (India / Gujarat)" workbook's
 * exact column layout, populated from the already-finalized figures in
 * payroll_items - a completed register of what was actually paid, not an
 * editable template someone will keep re-deriving formulas in, so cells
 * are written as literal values rather than live Excel formulas.
 *
 * Component-code buckets (DA/HRA/CONVEYANCE/SPECIAL, everything else ->
 * "Other Allowance") mirror SalaryComponentSeeder's codes. Gratuity
 * Provision is a fixed display-only accrual-rate constant here (not a
 * configurable statutory_rate_configs type) since the workbook itself
 * calls it "a suggested accounting provision, not an employee deduction".
 */
class SalaryRegisterExportService
{
    use AddsExcelLogo;

    private const GRATUITY_PROVISION_RATE = 0.0481;

    /** @var array<string, StatutoryRateConfig|null> */
    private array $pfConfigCache = [];

    public function build(PayrollCycle $cycle): Spreadsheet
    {
        $cycle->loadMissing(['school', 'items.employee', 'items.components.component', 'items.fnfSettlement', 'journalVoucher']);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Salary Register');

        $columns = $this->columnDefinitions();
        $lastColumnLetter = Coordinate::stringFromColumnIndex(count($columns));

        $sheet->setCellValue('A1', 'MONTHLY SALARY REGISTER - '.$cycle->school->name);
        $sheet->mergeCells("A1:{$lastColumnLetter}1");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(36);
        $this->addLogo($sheet);

        $sheet->setCellValue('A2', 'Salary Month');
        $sheet->setCellValue('B2', $cycle->label());
        $sheet->getStyle('A2')->getFont()->setBold(true);

        // Group header row (4) - merge consecutive columns sharing a group label.
        $col = 1;
        while ($col <= count($columns)) {
            $group = $columns[$col - 1]['group'];

            if ($group === null) {
                $col++;

                continue;
            }

            $start = $col;

            while ($col <= count($columns) && $columns[$col - 1]['group'] === $group) {
                $col++;
            }

            $end = $col - 1;
            $startLetter = Coordinate::stringFromColumnIndex($start);
            $endLetter = Coordinate::stringFromColumnIndex($end);

            $sheet->setCellValue("{$startLetter}4", $group);

            if ($end > $start) {
                $sheet->mergeCells("{$startLetter}4:{$endLetter}4");
            }
        }

        $sheet->getStyle("A4:{$lastColumnLetter}4")
            ->getFont()->setBold(true);
        $sheet->getStyle("A4:{$lastColumnLetter}4")
            ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9E1F2');

        foreach ($columns as $i => $column) {
            $letter = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue("{$letter}5", $column['header']);
        }

        $sheet->getStyle("A5:{$lastColumnLetter}5")->getFont()->setBold(true);
        $sheet->getStyle("A5:{$lastColumnLetter}5")->getAlignment()->setWrapText(true);
        $sheet->getStyle("A5:{$lastColumnLetter}5")
            ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F2F2F2');

        $row = 6;

        foreach ($cycle->items as $srNo => $item) {
            $values = $this->rowValues($cycle, $item, $srNo + 1);

            foreach ($columns as $i => $column) {
                $letter = Coordinate::stringFromColumnIndex($i + 1);
                $sheet->setCellValue("{$letter}{$row}", $values[$column['key']] ?? null);
            }

            $row++;
        }

        foreach (range(1, count($columns)) as $i) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }

        $sheet->freezePane('C6');
        $sheet->getStyle("A5:{$lastColumnLetter}5")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return $spreadsheet;
    }

    /**
     * @return array<int, array{key: string, header: string, group: ?string}>
     */
    private function columnDefinitions(): array
    {
        $col = fn (string $key, string $header, ?string $group = null) => ['key' => $key, 'header' => $header, 'group' => $group];

        return [
            $col('sr_no', 'Sr No.', 'EMPLOYEE & BANK DETAILS'),
            $col('employee_id', 'Employee ID', 'EMPLOYEE & BANK DETAILS'),
            $col('employee_name', 'Employee Name', 'EMPLOYEE & BANK DETAILS'),
            $col('department', 'Department / Branch', 'EMPLOYEE & BANK DETAILS'),
            $col('designation', 'Designation', 'EMPLOYEE & BANK DETAILS'),
            $col('date_of_joining', 'Date of Joining', 'EMPLOYEE & BANK DETAILS'),
            $col('pan', 'PAN', 'EMPLOYEE & BANK DETAILS'),
            $col('uan', 'UAN', 'EMPLOYEE & BANK DETAILS'),
            $col('esic_ip_no', 'ESIC IP No.', 'EMPLOYEE & BANK DETAILS'),
            $col('bank_account_no', 'Bank Account No.', 'EMPLOYEE & BANK DETAILS'),
            $col('ifsc', 'IFSC', 'EMPLOYEE & BANK DETAILS'),

            $col('salary_days', 'Salary Days', 'ATTENDANCE'),
            $col('present_days', 'Present Days', 'ATTENDANCE'),
            $col('paid_leave', 'Paid Leave', 'ATTENDANCE'),
            $col('weekly_off', 'Weekly Off / Paid Holiday', 'ATTENDANCE'),
            $col('lop_days', 'LOP Days', 'ATTENDANCE'),
            $col('payable_days', 'Payable Days', 'ATTENDANCE'),
            $col('ot_hours', 'OT Hours', 'ATTENDANCE'),

            $col('pf_applicable', 'PF Applicable?', 'STATUTORY APPLICABILITY'),
            $col('esi_applicable', 'ESI Applicable?', 'STATUTORY APPLICABILITY'),
            $col('pt_applicable', 'PT Applicable?', 'STATUTORY APPLICABILITY'),
            $col('lwf_applicable', 'LWF Applicable?', 'STATUTORY APPLICABILITY'),

            $col('monthly_basic', 'Monthly Basic', 'FIXED MONTHLY SALARY'),
            $col('monthly_da', 'Monthly DA', 'FIXED MONTHLY SALARY'),
            $col('monthly_hra', 'Monthly HRA', 'FIXED MONTHLY SALARY'),
            $col('monthly_conveyance', 'Monthly Conveyance', 'FIXED MONTHLY SALARY'),
            $col('monthly_special', 'Monthly Special Allowance', 'FIXED MONTHLY SALARY'),
            $col('monthly_other_allowance', 'Monthly Other Allowance', 'FIXED MONTHLY SALARY'),
            $col('fixed_gross', 'Fixed Gross', 'FIXED MONTHLY SALARY'),

            $col('earned_basic', 'Earned Basic', 'EARNED SALARY & OTHER EARNINGS'),
            $col('earned_da', 'Earned DA', 'EARNED SALARY & OTHER EARNINGS'),
            $col('earned_hra', 'Earned HRA', 'EARNED SALARY & OTHER EARNINGS'),
            $col('earned_conveyance', 'Earned Conveyance', 'EARNED SALARY & OTHER EARNINGS'),
            $col('earned_special', 'Earned Special Allowance', 'EARNED SALARY & OTHER EARNINGS'),
            $col('earned_other_allowance', 'Earned Other Allowance', 'EARNED SALARY & OTHER EARNINGS'),
            $col('ot_amount', 'OT Amount', 'EARNED SALARY & OTHER EARNINGS'),
            $col('incentive', 'Incentive', 'EARNED SALARY & OTHER EARNINGS'),
            $col('bonus', 'Bonus', 'EARNED SALARY & OTHER EARNINGS'),
            $col('arrears', 'Arrears', 'EARNED SALARY & OTHER EARNINGS'),
            $col('leave_encashment', 'Leave Encashment', 'EARNED SALARY & OTHER EARNINGS'),
            $col('other_earnings', 'Other Earnings', 'EARNED SALARY & OTHER EARNINGS'),
            $col('gross_pay', 'Gross Pay', 'EARNED SALARY & OTHER EARNINGS'),

            $col('pf_wage_override', 'PF Wage Override', 'STATUTORY DEDUCTIONS'),
            $col('pf_wages', 'PF Wages', 'STATUTORY DEDUCTIONS'),
            $col('employee_pf', 'Employee PF', 'STATUTORY DEDUCTIONS'),
            $col('esi_wage_override', 'ESI Wage Override', 'STATUTORY DEDUCTIONS'),
            $col('esi_wages', 'ESI Wages', 'STATUTORY DEDUCTIONS'),
            $col('employee_esi', 'Employee ESI', 'STATUTORY DEDUCTIONS'),
            $col('professional_tax', 'Professional Tax', 'STATUTORY DEDUCTIONS'),
            $col('employee_lwf', 'Employee LWF', 'STATUTORY DEDUCTIONS'),
            $col('income_tax_tds', 'Income Tax / TDS', 'STATUTORY DEDUCTIONS'),

            $col('salary_advance_recovery', 'Salary Advance Recovery', 'OTHER DEDUCTIONS / RECOVERIES'),
            $col('loan_recovery', 'Loan Recovery', 'OTHER DEDUCTIONS / RECOVERIES'),
            $col('notice_excess_recovery', 'Notice / Excess Recovery', 'OTHER DEDUCTIONS / RECOVERIES'),
            $col('canteen_accommodation', 'Canteen / Accommodation', 'OTHER DEDUCTIONS / RECOVERIES'),
            $col('insurance_vpf', 'Insurance / VPF', 'OTHER DEDUCTIONS / RECOVERIES'),
            $col('other_deduction', 'Other Deduction', 'OTHER DEDUCTIONS / RECOVERIES'),

            $col('total_deductions', 'Total Deductions', 'PAYROLL RESULT'),
            $col('net_pay', 'NET PAY', 'PAYROLL RESULT'),

            $col('employer_eps', 'Employer EPS', 'EMPLOYER CONTRIBUTIONS & COST'),
            $col('employer_epf_balance', 'Employer EPF Balance', 'EMPLOYER CONTRIBUTIONS & COST'),
            $col('employer_edli', 'Employer EDLI', 'EMPLOYER CONTRIBUTIONS & COST'),
            $col('employer_esi', 'Employer ESI', 'EMPLOYER CONTRIBUTIONS & COST'),
            $col('employer_lwf', 'Employer LWF', 'EMPLOYER CONTRIBUTIONS & COST'),
            $col('gratuity_provision', 'Gratuity Provision', 'EMPLOYER CONTRIBUTIONS & COST'),
            $col('other_employer_cost', 'Other Employer Cost', 'EMPLOYER CONTRIBUTIONS & COST'),
            $col('total_employer_contribution', 'Total Employer Contribution', 'EMPLOYER CONTRIBUTIONS & COST'),
            $col('total_employer_cost', 'TOTAL EMPLOYER COST', 'EMPLOYER CONTRIBUTIONS & COST'),

            $col('payment_status', 'Payment Status', 'PAYMENT DETAILS'),
            $col('payment_date', 'Payment Date', 'PAYMENT DETAILS'),
            $col('bank_utr_ref', 'Bank / UTR Ref', 'PAYMENT DETAILS'),
            $col('remarks', 'Remarks', 'PAYMENT DETAILS'),
        ];
    }

    /** @return array<string, mixed> */
    private function rowValues(PayrollCycle $cycle, PayrollItem $item, int $srNo): array
    {
        $employee = $item->employee;

        $fixed = $this->fixedMonthlyBuckets($employee, $cycle->periodEnd()->toDateString());
        $earned = $this->earnedBuckets($item);

        $leaveEncashment = 0.0; // folded into "Other Earnings" below - see class docblock.
        $otherEarnings = (float) $item->other_earnings_amount + (float) $item->reimbursements_total + (float) $item->fnf_amount;
        $incentive = $earned['incentive'];

        $grossPay = $earned['basic'] + $earned['da'] + $earned['hra'] + $earned['conveyance'] + $earned['special'] + $earned['other_allowance']
            + (float) $item->ot_amount + $incentive + (float) $item->bonus_amount + (float) $item->arrears_amount + $leaveEncashment + $otherEarnings;

        $pfConfig = $this->pfConfigFor($employee->school_id, $cycle->periodEnd()->toDateString());
        $epsRate = (float) ($pfConfig?->config['eps_rate'] ?? 0) / 100;
        $employerEps = round((float) $item->pf_wages * $epsRate, 2);
        $employerEpfBalance = max((float) $item->pf_employer - $employerEps, 0);

        $gratuityProvision = round((float) $item->basic * self::GRATUITY_PROVISION_RATE, 2);

        $totalEmployerContribution = $employerEps + $employerEpfBalance + (float) $item->pf_employer_edli
            + (float) $item->esi_employer + (float) $item->lwf_employer + $gratuityProvision;

        $voucher = $cycle->journalVoucher;

        return [
            'sr_no' => $srNo,
            'employee_id' => $employee->external_employee_code,
            'employee_name' => $employee->name,
            'department' => $employee->department,
            'designation' => $employee->designation,
            'date_of_joining' => $employee->date_of_joining?->format('d-M-Y'),
            'pan' => $employee->pan,
            'uan' => $employee->uan_number,
            'esic_ip_no' => $employee->esi_number,
            'bank_account_no' => $employee->bank_account_number,
            'ifsc' => $employee->bank_ifsc,

            'salary_days' => (float) $item->total_days,
            'present_days' => $item->present_days !== null ? (float) $item->present_days : null,
            'paid_leave' => $item->paid_leave_days !== null ? (float) $item->paid_leave_days : null,
            'weekly_off' => $item->weekly_off_days !== null ? (float) $item->weekly_off_days : null,
            'lop_days' => (float) $item->manual_lop_days,
            'payable_days' => (float) $item->payable_days,
            'ot_hours' => $item->ot_hours !== null ? (float) $item->ot_hours : null,

            'pf_applicable' => $employee->pf_applicable ? 'Yes' : 'No',
            'esi_applicable' => $employee->esi_applicable ? 'Yes' : 'No',
            'pt_applicable' => $employee->pt_applicable ? 'Yes' : 'No',
            'lwf_applicable' => $employee->lwf_applicable ? 'Yes' : 'No',

            'monthly_basic' => $fixed['basic'],
            'monthly_da' => $fixed['da'],
            'monthly_hra' => $fixed['hra'],
            'monthly_conveyance' => $fixed['conveyance'],
            'monthly_special' => $fixed['special'],
            'monthly_other_allowance' => $fixed['other_allowance'],
            'fixed_gross' => $fixed['basic'] + $fixed['da'] + $fixed['hra'] + $fixed['conveyance'] + $fixed['special'] + $fixed['other_allowance'],

            'earned_basic' => $earned['basic'],
            'earned_da' => $earned['da'],
            'earned_hra' => $earned['hra'],
            'earned_conveyance' => $earned['conveyance'],
            'earned_special' => $earned['special'],
            'earned_other_allowance' => $earned['other_allowance'],
            'ot_amount' => (float) $item->ot_amount,
            'incentive' => $incentive,
            'bonus' => (float) $item->bonus_amount,
            'arrears' => (float) $item->arrears_amount,
            'leave_encashment' => $leaveEncashment,
            'other_earnings' => $otherEarnings,
            'gross_pay' => round($grossPay, 2),

            'pf_wage_override' => $item->pf_wage_override !== null ? (float) $item->pf_wage_override : null,
            'pf_wages' => (float) $item->pf_wages,
            'employee_pf' => (float) $item->pf_employee,
            'esi_wage_override' => $item->esi_wage_override !== null ? (float) $item->esi_wage_override : null,
            'esi_wages' => (float) $item->esi_wages,
            'employee_esi' => (float) $item->esi_employee,
            'professional_tax' => (float) $item->pt,
            'employee_lwf' => (float) $item->lwf_employee,
            'income_tax_tds' => (float) $item->tds,

            'salary_advance_recovery' => 0,
            'loan_recovery' => 0,
            'notice_excess_recovery' => 0,
            'canteen_accommodation' => 0,
            'insurance_vpf' => 0,
            'other_deduction' => (float) $item->other_deduction_amount,

            'total_deductions' => (float) $item->gross_deductions,
            'net_pay' => (float) $item->net_pay,

            'employer_eps' => $employerEps,
            'employer_epf_balance' => round($employerEpfBalance, 2),
            'employer_edli' => (float) $item->pf_employer_edli,
            'employer_esi' => (float) $item->esi_employer,
            'employer_lwf' => (float) $item->lwf_employer,
            'gratuity_provision' => $gratuityProvision,
            'other_employer_cost' => 0,
            'total_employer_contribution' => round($totalEmployerContribution, 2),
            'total_employer_cost' => round($grossPay + $totalEmployerContribution, 2),

            'payment_status' => $cycle->status === 'posted' ? 'Paid' : 'Pending',
            'payment_date' => $voucher?->posted_at?->format('d-M-Y'),
            'bank_utr_ref' => $voucher?->external_reference,
            'remarks' => $item->is_fnf ? 'Full & Final settlement month' : null,
        ];
    }

    /** Fixed monthly figures from the employee's active salary structure, bucketed by component code. */
    private function fixedMonthlyBuckets(Employee $employee, string $asOfDate): array
    {
        $buckets = ['basic' => 0.0, 'da' => 0.0, 'hra' => 0.0, 'conveyance' => 0.0, 'special' => 0.0, 'other_allowance' => 0.0];

        $structure = $employee->activeSalaryStructure($asOfDate);

        if (! $structure) {
            return $buckets;
        }

        $buckets['basic'] = (float) $structure->basic;

        foreach ($structure->lines as $line) {
            if (! $line->component->isEarning()) {
                continue;
            }

            $amount = $line->resolveAmount((float) $structure->basic);
            $bucket = match ($line->component->code) {
                'DA' => 'da',
                'HRA' => 'hra',
                'CONVEYANCE' => 'conveyance',
                'SPECIAL' => 'special',
                'INCENTIVE' => null, // shown separately in the Earned Salary section
                default => 'other_allowance',
            };

            if ($bucket !== null) {
                $buckets[$bucket] += $amount;
            }
        }

        return $buckets;
    }

    /** Earned (prorated) figures from the payroll item's stored components, same bucketing. */
    private function earnedBuckets(PayrollItem $item): array
    {
        $buckets = ['basic' => (float) $item->basic, 'da' => 0.0, 'hra' => 0.0, 'conveyance' => 0.0, 'special' => 0.0, 'other_allowance' => 0.0, 'incentive' => 0.0];

        foreach ($item->components as $line) {
            if ($line->type !== 'earning') {
                continue;
            }

            $bucket = match ($line->component->code) {
                'DA' => 'da',
                'HRA' => 'hra',
                'CONVEYANCE' => 'conveyance',
                'SPECIAL' => 'special',
                'INCENTIVE' => 'incentive',
                default => 'other_allowance',
            };

            $buckets[$bucket] += (float) $line->amount;
        }

        return $buckets;
    }

    private function pfConfigFor(?int $schoolId, string $asOfDate): ?StatutoryRateConfig
    {
        $cacheKey = "{$schoolId}:{$asOfDate}";

        return $this->pfConfigCache[$cacheKey] ??= StatutoryRateConfig::activeFor($schoolId, 'PF', $asOfDate);
    }
}
