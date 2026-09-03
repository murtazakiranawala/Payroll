<?php

namespace App\Services;

use App\Contracts\FinancialPostingProviderInterface;
use App\Models\GlAccountMapping;
use App\Models\JournalVoucher;
use App\Models\JournalVoucherLine;
use App\Models\PayrollCycle;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * BRD FR-3: builds the payroll journal voucher for a finalized cycle and
 * posts it to the Financial ERP. Voucher generation happens automatically
 * on finance approval (FR-3.1); posting is a separate explicit step so a
 * user can review the voucher first (FR-3.3). Duplicate posting is
 * prevented by a unique idempotency_key per cycle (FR-3.5) and a row lock
 * around the status transition.
 */
class JournalVoucherService
{
    public function __construct(private FinancialPostingProviderInterface $provider)
    {
    }

    public function buildForCycle(PayrollCycle $cycle): JournalVoucher
    {
        if ($cycle->status !== 'approved') {
            throw new RuntimeException('A journal voucher can only be built for an approved payroll cycle.');
        }

        $idempotencyKey = "cycle-{$cycle->id}";

        $existing = JournalVoucher::where('idempotency_key', $idempotencyKey)->first();

        if ($existing) {
            return $existing;
        }

        $items = $cycle->items;

        if ($items->isEmpty()) {
            throw new RuntimeException('This payroll cycle has no payroll items to post.');
        }

        // EDLI is an additional employer-only PF cost paid to EPFO via the same
        // combined challan as PF/EPS, so it folds into the existing PF expense/
        // liability lines rather than needing its own GL category.
        $pfEmployerTotal = (float) $items->sum('pf_employer') + (float) $items->sum('pf_employer_edli');

        // salary_expense must cover everything net_pay_payable (credit) draws
        // from on the earnings side, beyond the structure-derived gross_earnings:
        // OT/bonus/arrears/other-earnings are manual per-cycle earnings that
        // still count as salary cost (reimbursements and F&F have their own
        // dedicated expense lines below).
        $salaryExpense = (float) $items->sum('gross_earnings')
            + (float) $items->sum('ot_amount')
            + (float) $items->sum('bonus_amount')
            + (float) $items->sum('arrears_amount')
            + (float) $items->sum('other_earnings_amount');

        $totals = [
            'salary_expense' => $salaryExpense,
            'pf_employer_expense' => $pfEmployerTotal,
            'esi_employer_expense' => (float) $items->sum('esi_employer'),
            'lwf_employer_expense' => (float) $items->sum('lwf_employer'),
            'reimbursement_expense' => (float) $items->sum('reimbursements_total'),
            'fnf_expense' => (float) $items->sum('fnf_amount'),
            'pf_liability' => (float) $items->sum('pf_employee') + $pfEmployerTotal,
            'esi_liability' => (float) $items->sum('esi_employee') + (float) $items->sum('esi_employer'),
            'tds_payable' => (float) $items->sum('tds'),
            'pt_payable' => (float) $items->sum('pt'),
            'lwf_liability' => (float) $items->sum('lwf_employee') + (float) $items->sum('lwf_employer'),
            // Loan/advance/canteen/etc. recoveries withheld from pay but not
            // yet remitted anywhere specific - a holding liability until
            // reconciled against whichever ledger they're actually owed to.
            'other_deductions_payable' => (float) $items->sum('other_deduction_amount'),
            'net_pay_payable' => (float) $items->sum('net_pay'),
        ];

        $debitCategories = ['salary_expense', 'pf_employer_expense', 'esi_employer_expense', 'lwf_employer_expense', 'reimbursement_expense', 'fnf_expense'];

        $missingMappings = [];
        $lines = [];

        foreach ($totals as $category => $amount) {
            if (round($amount, 2) === 0.0) {
                continue;
            }

            $mapping = GlAccountMapping::resolve($cycle->school_id, $category);

            if (! $mapping) {
                $missingMappings[] = $category;

                continue;
            }

            $lines[] = [
                'category' => $category,
                'gl_account_code' => $mapping->gl_account_code,
                'cost_centre_code' => $mapping->cost_centre_code,
                'debit' => in_array($category, $debitCategories, true) ? round($amount, 2) : 0,
                'credit' => in_array($category, $debitCategories, true) ? 0 : round($amount, 2),
                'description' => "{$cycle->school->name} payroll {$cycle->label()} - ".str_replace('_', ' ', $category),
            ];
        }

        if (! empty($missingMappings)) {
            throw new RuntimeException(
                'GL account mapping missing for: '.implode(', ', $missingMappings).
                '. Configure these under Schools > GL Mappings before generating the voucher.'
            );
        }

        return DB::transaction(function () use ($cycle, $idempotencyKey, $lines) {
            $voucher = JournalVoucher::create([
                'payroll_cycle_id' => $cycle->id,
                'voucher_number' => 'JV-'.$cycle->school->code.'-'.$cycle->year.str_pad((string) $cycle->month, 2, '0', STR_PAD_LEFT),
                'status' => 'draft',
                'idempotency_key' => $idempotencyKey,
            ]);

            foreach ($lines as $line) {
                JournalVoucherLine::create($line + ['journal_voucher_id' => $voucher->id]);
            }

            return $voucher->fresh('lines');
        });
    }

    public function post(JournalVoucher $voucher): JournalVoucher
    {
        return DB::transaction(function () use ($voucher) {
            $locked = JournalVoucher::whereKey($voucher->id)->lockForUpdate()->first();

            if ($locked->status !== 'draft') {
                throw new RuntimeException("This voucher is already [{$locked->status}] - it cannot be posted again.");
            }

            $result = $this->provider->postVoucher($voucher->load('lines'));

            $locked->update([
                'status' => 'posted',
                'external_reference' => $result['external_reference'],
                'posted_at' => $result['posted_at'] ?? now(),
            ]);

            $locked->payrollCycle->update(['status' => 'posted']);

            return $locked->fresh('lines');
        });
    }

    public function reverse(JournalVoucher $voucher, string $reason): JournalVoucher
    {
        if ($voucher->status !== 'posted') {
            throw new RuntimeException('Only a posted voucher can be reversed.');
        }

        return DB::transaction(function () use ($voucher, $reason) {
            $reversal = JournalVoucher::create([
                'payroll_cycle_id' => $voucher->payroll_cycle_id,
                'voucher_number' => $voucher->voucher_number.'-REV',
                'status' => 'draft',
                'idempotency_key' => $voucher->idempotency_key.'-reversal',
                'reversal_of_voucher_id' => $voucher->id,
            ]);

            foreach ($voucher->lines as $line) {
                JournalVoucherLine::create([
                    'journal_voucher_id' => $reversal->id,
                    'category' => $line->category,
                    'gl_account_code' => $line->gl_account_code,
                    'cost_centre_code' => $line->cost_centre_code,
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'description' => 'Reversal: '.$line->description." ({$reason})",
                ]);
            }

            $result = $this->provider->reverseVoucher($voucher, $reversal->fresh('lines'));

            $reversal->update([
                'status' => 'posted',
                'external_reference' => $result['external_reference'],
                'posted_at' => $result['posted_at'] ?? now(),
            ]);

            $voucher->update(['status' => 'reversed', 'reversed_at' => now()]);
            $voucher->payrollCycle->update(['status' => 'reversed']);

            return $reversal->fresh('lines');
        });
    }
}
