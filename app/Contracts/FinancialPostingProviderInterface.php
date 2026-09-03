<?php

namespace App\Contracts;

use App\Models\JournalVoucher;

/**
 * Destination-side of BRD FR-3: posting finalized payroll journal vouchers
 * into the in-house Financial ERP general ledger.
 */
interface FinancialPostingProviderInterface
{
    /**
     * Post a voucher (with its lines already built) into the Financial ERP.
     * Must be idempotent from the caller's perspective — JournalVoucherService
     * guarantees it is only ever called once per voucher via a DB-level guard,
     * but a well-behaved provider should still tolerate a retried call safely.
     *
     * @return array{external_reference: string, posted_at: string}
     */
    public function postVoucher(JournalVoucher $voucher): array;

    /**
     * Post a reversing/adjustment voucher against a previously posted one.
     *
     * @return array{external_reference: string, posted_at: string}
     */
    public function reverseVoucher(JournalVoucher $originalVoucher, JournalVoucher $reversalVoucher): array;
}
