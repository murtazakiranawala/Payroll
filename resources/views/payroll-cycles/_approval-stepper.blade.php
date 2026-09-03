{{--
    Visual pipeline for a payroll cycle: draft -> computed -> HR approved ->
    Finance approved -> posted. Every timestamp/name here comes straight off
    the PayrollCycle/JournalVoucher records - nothing invented.
--}}
@php
    $steps = [
        [
            'title' => 'Draft Created',
            'done' => true,
            'current' => false,
            'meta' => $cycle->created_at->format('M j, Y'),
            'by' => $cycle->creator->name ?? 'System',
        ],
        [
            'title' => 'Payroll Processed',
            'done' => (bool) $cycle->computed_at,
            'current' => $cycle->status === 'draft' && ! $cycle->computed_at,
            'meta' => $cycle->computed_at?->format('M j, Y'),
            'by' => null,
        ],
        [
            'title' => 'HR Approved',
            'done' => (bool) $cycle->hr_reviewed_at,
            'current' => $cycle->status === 'hr_review',
            'meta' => $cycle->hr_reviewed_at?->format('M j, Y'),
            'by' => $cycle->hrReviewer->name ?? null,
        ],
        [
            'title' => 'Finance Approved',
            'done' => (bool) $cycle->finance_approved_at,
            'current' => $cycle->status === 'finance_review',
            'meta' => $cycle->finance_approved_at?->format('M j, Y'),
            'by' => $cycle->financeApprover->name ?? null,
        ],
        [
            'title' => 'Posted to Financial ERP',
            'done' => $cycle->journalVoucher?->status === 'posted',
            'current' => $cycle->status === 'approved',
            'meta' => $cycle->journalVoucher?->status === 'posted' ? $cycle->journalVoucher->posted_at?->format('M j, Y') : null,
            'by' => $cycle->journalVoucher?->status === 'posted' ? 'Finance System' : null,
        ],
    ];
@endphp
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="fw-semibold">Approval Workflow</div>
            <x-status-badge :status="$cycle->status" />
        </div>
        <div class="approval-stepper">
            @foreach ($steps as $step)
                <div class="approval-step {{ $step['done'] ? 'is-done' : ($step['current'] ? 'is-current' : '') }}">
                    <div class="step-icon">
                        @if ($step['done'])
                            <i class="bi bi-check-lg"></i>
                        @elseif ($step['current'])
                            <i class="bi bi-clock"></i>
                        @else
                            <i class="bi bi-circle"></i>
                        @endif
                    </div>
                    <div class="step-title">{{ $step['title'] }}</div>
                    <div class="step-meta">
                        @if ($step['meta'])
                            {{ $step['meta'] }}
                        @elseif ($step['current'])
                            Pending
                        @else
                            &mdash;
                        @endif
                        @if ($step['by'])<br>{{ $step['by'] }}@endif
                    </div>
                </div>
            @endforeach
        </div>

        @if ($cycle->status === 'hr_review')
            <div class="alert alert-warning d-flex align-items-start gap-2 mt-4 mb-0">
                <i class="bi bi-info-circle mt-1"></i>
                <div>Awaiting HR review. Once approved, this cycle moves to Finance for final sign-off.</div>
            </div>
        @elseif ($cycle->status === 'finance_review')
            <div class="alert alert-warning d-flex align-items-start gap-2 mt-4 mb-0">
                <i class="bi bi-info-circle mt-1"></i>
                <div>Awaiting Finance approval before a journal voucher can be posted to the Financial ERP.</div>
            </div>
        @elseif ($cycle->status === 'approved' && ! $cycle->journalVoucher)
            <div class="alert alert-info d-flex align-items-start gap-2 mt-4 mb-0">
                <i class="bi bi-info-circle mt-1"></i>
                <div>Approved. Generate the journal voucher to proceed toward posting.</div>
            </div>
        @elseif ($cycle->status === 'approved' && $cycle->journalVoucher?->status === 'draft')
            <div class="alert alert-info d-flex align-items-start gap-2 mt-4 mb-0">
                <i class="bi bi-info-circle mt-1"></i>
                <div>Journal voucher generated and ready for review &mdash; post it to the Financial ERP to complete this cycle.</div>
            </div>
        @elseif ($cycle->status === 'reversed')
            <div class="alert alert-danger d-flex align-items-start gap-2 mt-4 mb-0">
                <i class="bi bi-exclamation-triangle mt-1"></i>
                <div>This payroll cycle's posting was reversed.</div>
            </div>
        @endif
    </div>
</div>
