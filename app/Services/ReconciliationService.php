<?php

namespace App\Services;

use App\Models\PayrollCycle;
use App\Models\ReconciliationRecord;

/**
 * BRD process flow step 9 / reporting requirements: compares the payroll
 * register total (sum of net pay computed by the Payroll module) against
 * what was actually posted to the Financial ERP's net-pay-payable account,
 * surfacing any variance.
 */
class ReconciliationService
{
    public function generate(PayrollCycle $cycle): ReconciliationRecord
    {
        $registerTotal = (float) $cycle->items()->sum('net_pay');

        $voucher = $cycle->journalVoucher;
        $postedTotal = $voucher
            ? (float) $voucher->lines->where('category', 'net_pay_payable')->sum('credit')
            : 0.0;

        $variance = round($registerTotal - $postedTotal, 2);

        return ReconciliationRecord::updateOrCreate(
            ['payroll_cycle_id' => $cycle->id],
            [
                'payroll_register_total' => $registerTotal,
                'financial_erp_posted_total' => $postedTotal,
                'variance' => $variance,
                'status' => abs($variance) < 0.01 ? 'matched' : 'variance',
                'generated_at' => now(),
            ]
        );
    }
}
