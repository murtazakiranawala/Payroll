<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
    h2 { margin-bottom: 0; }
    .muted { color: #666; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #ccc; padding: 5px 8px; text-align: left; }
    th { background: #f2f2f2; }
    .totals td { font-weight: bold; }
    .header-table td { border: none; padding: 2px 4px; }
    .text-end { text-align: right; }
    .letterhead { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    .letterhead td { border: none; padding: 0; vertical-align: middle; }
    .letterhead img { width: 56px; height: 56px; }
</style>
</head>
<body>
    <table class="letterhead">
        <tr>
            <td style="width: 64px;"><img src="{{ public_path('images/logo.png') }}" alt="Logo"></td>
            <td>
                <h2 style="margin-bottom: 2px;">{{ $item->employee->school->name }}</h2>
                <div class="muted" style="font-size: 10px;">{{ config('app.name') }}</div>
            </td>
        </tr>
    </table>
    <div class="muted">Payslip for {{ $item->payrollCycle->label() }}</div>

    <table class="header-table">
        <tr>
            <td><strong>Employee:</strong> {{ $item->employee->name }}</td>
            <td><strong>Code:</strong> {{ $item->employee->external_employee_code }}</td>
        </tr>
        <tr>
            <td><strong>Designation:</strong> {{ $item->employee->designation }}</td>
            <td><strong>Department:</strong> {{ $item->employee->department }}</td>
        </tr>
        <tr>
            <td><strong>Paid Days:</strong> {{ $item->payable_days }} / {{ $item->total_days }}</td>
            <td><strong>Bank A/C:</strong> {{ $item->employee->maskedBankAccount() }} ({{ $item->employee->bank_ifsc }})</td>
        </tr>
    </table>

    @php
        $earnings = [['Basic Salary', $item->basic]];
        foreach ($item->components->where('type', 'earning') as $line) {
            $earnings[] = [$line->component->name, $line->amount];
        }
        if ($item->ot_amount > 0) $earnings[] = ['Overtime', $item->ot_amount];
        if ($item->bonus_amount > 0) $earnings[] = ['Bonus', $item->bonus_amount];
        if ($item->arrears_amount > 0) $earnings[] = ['Arrears', $item->arrears_amount];
        if ($item->other_earnings_amount > 0) $earnings[] = ['Other Earnings', $item->other_earnings_amount];
        if ($item->reimbursements_total > 0) {
            $earnings[] = ['Reimbursements', $item->reimbursements_total];
        }
        if ($item->is_fnf && $item->fnf_amount > 0) {
            $earnings[] = ['Full & Final Settlement', $item->fnf_amount];
        }

        $deductions = [];
        if ($item->pf_employee > 0) $deductions[] = ['Provident Fund', $item->pf_employee];
        if ($item->esi_employee > 0) $deductions[] = ['ESI', $item->esi_employee];
        if ($item->pt > 0) $deductions[] = ['Professional Tax', $item->pt];
        if ($item->tds > 0) $deductions[] = ['TDS', $item->tds];
        if ($item->lwf_employee > 0) $deductions[] = ['Labour Welfare Fund', $item->lwf_employee];
        if ($item->other_deduction_amount > 0) $deductions[] = ['Other Deductions', $item->other_deduction_amount];

        $rowCount = max(count($earnings), count($deductions));
        $totalEarnings = $item->gross_earnings + $item->ot_amount + $item->bonus_amount + $item->arrears_amount
            + $item->other_earnings_amount + $item->reimbursements_total + $item->fnf_amount;
    @endphp

    <table>
        <thead>
            <tr><th>Earnings</th><th class="text-end">Amount</th><th>Deductions</th><th class="text-end">Amount</th></tr>
        </thead>
        <tbody>
            @for ($i = 0; $i < $rowCount; $i++)
                <tr>
                    <td>{{ $earnings[$i][0] ?? '' }}</td>
                    <td class="text-end">{{ isset($earnings[$i]) ? number_format($earnings[$i][1], 2) : '' }}</td>
                    <td>{{ $deductions[$i][0] ?? '' }}</td>
                    <td class="text-end">{{ isset($deductions[$i]) ? number_format($deductions[$i][1], 2) : '' }}</td>
                </tr>
            @endfor
        </tbody>
        <tfoot>
            <tr class="totals">
                <td>Gross Earnings</td>
                <td class="text-end">{{ number_format($totalEarnings, 2) }}</td>
                <td>Total Deductions</td>
                <td class="text-end">{{ number_format($item->gross_deductions, 2) }}</td>
            </tr>
            <tr class="totals">
                <td colspan="3">Net Pay</td>
                <td class="text-end">{{ number_format($item->net_pay, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <p class="muted" style="margin-top: 20px;">This is a system-generated payslip and does not require a signature.</p>
</body>
</html>
