<?php

namespace App\Http\Controllers;

use App\Models\PayrollCycle;
use App\Models\PayrollItem;
use App\Services\PayslipService;

class PayslipController extends Controller
{
    public function show(PayrollCycle $cycle, PayrollItem $item, PayslipService $service)
    {
        abort_if($item->payroll_cycle_id !== $cycle->id, 404);

        $filename = "payslip-{$item->employee->external_employee_code}-{$cycle->year}-{$cycle->month}.pdf";

        return $service->render($item)->stream($filename);
    }
}
