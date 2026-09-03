<?php

namespace App\Services;

use App\Models\FnfSettlement;

/**
 * BRD FR-2.4: Full & Final settlement for exiting employees - leave
 * encashment, gratuity (Payment of Gratuity Act default: 15/26 x last
 * drawn basic x completed years of service, eligible from 5 completed
 * years), notice pay and recoveries. Leave balance and notice period are
 * HR-entered here since this BRD does not define a dedicated leave-ledger
 * module to source them from automatically.
 */
class FnfSettlementService
{
    public function recalculate(FnfSettlement $settlement, array $inputs): FnfSettlement
    {
        $item = $settlement->payrollItem;
        $lastDrawnBasic = $item ? (float) $item->basic : 0.0;
        $dailyBasic = $lastDrawnBasic > 0 ? $lastDrawnBasic / 30 : 0.0;

        $leaveDays = (float) ($inputs['leave_encashment_days'] ?? 0);
        $noticeDays = (float) ($inputs['notice_pay_days'] ?? 0);
        $recoveries = (float) ($inputs['recoveries_amount'] ?? 0);
        $gratuityOverride = array_key_exists('gratuity_override', $inputs) && $inputs['gratuity_override'] !== null
            ? (float) $inputs['gratuity_override']
            : null;

        $leaveAmount = round($leaveDays * $dailyBasic, 2);
        $noticeAmount = round($noticeDays * $dailyBasic, 2);

        $gratuityAmount = $gratuityOverride;

        if ($gratuityAmount === null) {
            $gratuityAmount = $settlement->gratuity_eligible
                ? round((15 / 26) * $lastDrawnBasic * (float) $settlement->completed_years_of_service, 2)
                : 0.0;
        }

        $netFnf = round($leaveAmount + $gratuityAmount + $noticeAmount - $recoveries, 2);

        $settlement->fill([
            'leave_encashment_days' => $leaveDays,
            'leave_encashment_amount' => $leaveAmount,
            'gratuity_amount' => $gratuityAmount,
            'notice_pay_days' => $noticeDays,
            'notice_pay_amount' => $noticeAmount,
            'recoveries_amount' => $recoveries,
            'net_fnf_amount' => $netFnf,
            'remarks' => $inputs['remarks'] ?? $settlement->remarks,
            'finalized' => true,
        ]);
        $settlement->save();

        if ($item) {
            $regularNetPay = round($item->net_pay - (float) $item->fnf_amount, 2);

            $item->update([
                'fnf_amount' => $netFnf,
                'net_pay' => round($regularNetPay + $netFnf, 2),
            ]);
        }

        return $settlement;
    }
}
