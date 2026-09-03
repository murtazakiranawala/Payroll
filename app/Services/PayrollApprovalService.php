<?php

namespace App\Services;

use App\Models\PayrollCycle;
use App\Models\User;
use App\Support\Role;
use RuntimeException;

/**
 * BRD FR-2.5/FR-2.7: draft -> hr_review -> finance_review -> approved
 * workflow, with the ability to reject back to draft (with a reason) or -
 * prior to Financial ERP posting - pull an approved cycle back for
 * correction.
 */
class PayrollApprovalService
{
    public function submitForHrReview(PayrollCycle $cycle, User $user): PayrollCycle
    {
        $this->assertStatus($cycle, 'draft');
        $this->assertRole($user, Role::HR);

        if ($cycle->items()->count() === 0) {
            throw new RuntimeException('Run payroll computation before submitting the cycle for review.');
        }

        $cycle->update(['status' => 'hr_review', 'created_by' => $cycle->created_by ?? $user->id]);

        return $cycle->fresh();
    }

    public function approveByHr(PayrollCycle $cycle, User $user): PayrollCycle
    {
        $this->assertStatus($cycle, 'hr_review');
        $this->assertRole($user, Role::HR);

        $cycle->update([
            'status' => 'finance_review',
            'hr_reviewed_by' => $user->id,
            'hr_reviewed_at' => now(),
        ]);

        return $cycle->fresh();
    }

    public function approveByFinance(PayrollCycle $cycle, User $user): PayrollCycle
    {
        $this->assertStatus($cycle, 'finance_review');
        $this->assertRole($user, Role::FINANCE);

        $cycle->update([
            'status' => 'approved',
            'finance_approved_by' => $user->id,
            'finance_approved_at' => now(),
        ]);

        return $cycle->fresh();
    }

    public function reject(PayrollCycle $cycle, User $user, string $reason): PayrollCycle
    {
        if (! in_array($cycle->status, ['hr_review', 'finance_review'], true)) {
            throw new RuntimeException('Only a cycle in review can be rejected back to draft.');
        }

        $this->assertRole($user, Role::HR, Role::FINANCE);

        $cycle->update([
            'status' => 'draft',
            'rejected_reason' => $reason,
            'hr_reviewed_by' => null,
            'hr_reviewed_at' => null,
        ]);

        return $cycle->fresh();
    }

    /**
     * BRD FR-2.7: allow a finalized cycle to be corrected prior to financial
     * posting. Blocked once a journal voucher has actually been posted.
     */
    public function reopenForCorrection(PayrollCycle $cycle, User $user): PayrollCycle
    {
        if ($cycle->status !== 'approved') {
            throw new RuntimeException('Only an approved (not yet posted) cycle can be reopened for correction.');
        }

        $this->assertRole($user, Role::FINANCE);

        if ($cycle->journalVoucher && $cycle->journalVoucher->status === 'posted') {
            throw new RuntimeException('This cycle has already been posted to the Financial ERP. Use a reversal instead of reopening it.');
        }

        $cycle->journalVoucher?->delete();

        $cycle->update([
            'status' => 'draft',
            'finance_approved_by' => null,
            'finance_approved_at' => null,
            'hr_reviewed_by' => null,
            'hr_reviewed_at' => null,
        ]);

        return $cycle->fresh();
    }

    private function assertStatus(PayrollCycle $cycle, string $expected): void
    {
        if ($cycle->status !== $expected) {
            throw new RuntimeException("Cycle must be in [{$expected}] status for this action (currently [{$cycle->status}]).");
        }
    }

    private function assertRole(User $user, string ...$roles): void
    {
        if (! $user->hasRole(...$roles)) {
            throw new RuntimeException('You do not have permission to perform this action.');
        }
    }
}
