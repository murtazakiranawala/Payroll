<?php

namespace App\Services\Integrations;

use App\Contracts\FinancialPostingProviderInterface;
use App\Models\JournalVoucher;

/**
 * Simulates the Financial ERP accepting a posted voucher, so the full
 * approve -> post -> reconcile loop can be demoed before the real
 * Financial ERP posting endpoint is confirmed (BRD §6.2).
 */
class FinancialErpMockProvider implements FinancialPostingProviderInterface
{
    public function postVoucher(JournalVoucher $voucher): array
    {
        return [
            'external_reference' => 'MOCK-FIN-'.$voucher->voucher_number,
            'posted_at' => now()->toIso8601String(),
        ];
    }

    public function reverseVoucher(JournalVoucher $originalVoucher, JournalVoucher $reversalVoucher): array
    {
        return [
            'external_reference' => 'MOCK-FIN-REV-'.$reversalVoucher->voucher_number,
            'posted_at' => now()->toIso8601String(),
        ];
    }
}
