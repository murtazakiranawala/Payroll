{{--
    Central status → color mapping so every status badge in the app (payroll
    cycle stage, journal voucher, sync log, reimbursement claim, reconciliation,
    employment status, etc.) is colour-coded consistently instead of every
    view hardcoding its own bg-secondary/bg-info per status string.

    Uses Bootstrap 5.3's `text-bg-*` helpers (not plain `bg-*`) so the badge
    text colour has correct contrast against light backgrounds like warning/
    info/light — plain `bg-warning`/`bg-info` badges default to white text,
    which is hard to read.

    Usage: <x-status-badge :status="$cycle->status" />
           <x-status-badge :status="$employee->employment_status" />
--}}
@props(['status'])
@php
    $normalized = strtolower(trim((string) $status));

    $variants = [
        // payroll cycle workflow
        'draft' => 'secondary',
        'hr_review' => 'info',
        'finance_review' => 'info',
        'approved' => 'success',
        'posted' => 'success',
        'reversed' => 'dark',
        // journal voucher
        // ('draft' / 'posted' / 'reversed' shared with above)

        // employee sync log
        'running' => 'info',
        'completed' => 'success',
        'completed_with_errors' => 'warning',
        'failed' => 'danger',

        // reimbursement claim
        'pending' => 'warning',
        'rejected' => 'danger',

        // reconciliation
        'matched' => 'success',
        'variance' => 'danger',
        'mismatched' => 'danger',

        // employee / school active state
        'active' => 'success',
        'inactive' => 'secondary',
        'on_leave' => 'warning',
        'exited' => 'secondary',

        // staff grade compensation compliance (Staff Grading & Compensation Policy)
        'below_min' => 'warning',
        'within_band' => 'success',
        'above_max' => 'danger',
    ];

    $variant = $variants[$normalized] ?? 'secondary';
@endphp
<span {{ $attributes->merge(['class' => "badge rounded-pill text-bg-$variant badge-status"]) }}>{{ str_replace('_', ' ', (string) $status) }}</span>
