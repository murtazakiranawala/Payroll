<?php

namespace App\Services;

use App\Models\PayrollItem;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * BRD FR: professional monthly payslips (school details, employee info, pay
 * period, earnings/deductions breakup, net salary, paid days, bank info),
 * exportable as PDF.
 */
class PayslipService
{
    public function render(PayrollItem $item)
    {
        $item->loadMissing(['employee.school', 'payrollCycle', 'components.component', 'fnfSettlement']);

        return Pdf::loadView('payslips.pdf', ['item' => $item]);
    }
}
