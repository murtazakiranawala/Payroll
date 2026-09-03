<?php

namespace App\Http\Controllers;

use App\Models\JournalVoucher;
use App\Models\PayrollCycle;
use App\Services\JournalVoucherService;
use Illuminate\Http\Request;

class JournalVoucherController extends Controller
{
    public function build(PayrollCycle $cycle, JournalVoucherService $service)
    {
        try {
            $service->buildForCycle($cycle);

            return back()->with('status', 'Journal voucher generated. Review it before posting to the Financial ERP.');
        } catch (\Throwable $e) {
            return back()->withErrors($e->getMessage());
        }
    }

    public function post(JournalVoucher $journalVoucher, JournalVoucherService $service)
    {
        try {
            $service->post($journalVoucher);

            return back()->with('status', 'Journal voucher posted to the Financial ERP.');
        } catch (\Throwable $e) {
            return back()->withErrors($e->getMessage());
        }
    }

    public function reverse(Request $request, JournalVoucher $journalVoucher, JournalVoucherService $service)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        try {
            $service->reverse($journalVoucher, $data['reason']);

            return back()->with('status', 'Reversal voucher posted to the Financial ERP.');
        } catch (\Throwable $e) {
            return back()->withErrors($e->getMessage());
        }
    }
}
