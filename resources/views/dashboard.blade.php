@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $fmt = fn ($v) => '₹ ' . number_format((float) $v, 0);
    $pct = fn ($v) => ($v === null ? null : ($v >= 0 ? '+' : '') . number_format($v, 1) . '% vs last month');
    $dir = fn ($v) => $v === null ? 'neutral' : ($v >= 0 ? 'up' : 'down');

    $turnaround = '—';
    if ($avgTurnaroundMinutes !== null) {
        $turnaround = $avgTurnaroundMinutes >= 1440
            ? number_format($avgTurnaroundMinutes / 1440, 1) . 'd'
            : ($avgTurnaroundMinutes >= 60 ? intdiv($avgTurnaroundMinutes, 60) . 'h ' . ($avgTurnaroundMinutes % 60) . 'm' : $avgTurnaroundMinutes . 'm');
    }

    $syncTone = match (true) {
        ! $lastSync => 'default',
        $lastSync->status === 'completed' => 'success',
        $lastSync->status === 'completed_with_errors' => 'warning',
        $lastSync->status === 'failed' => 'danger',
        default => 'default',
    };
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="text-muted small">
        @if ($schoolIds)
            Showing <strong>{{ count($schoolIds) }}</strong> of {{ $schools->count() }} schools
        @else
            Showing all {{ $schools->count() }} schools
        @endif
    </div>
    <div class="dropdown">
        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
            <i class="bi bi-building"></i> Filter by School
            @if ($schoolIds)
                <span class="badge rounded-pill text-bg-primary ms-1">{{ count($schoolIds) }}</span>
            @endif
        </button>
        <form method="GET" action="{{ route('dashboard') }}" class="dropdown-menu dropdown-menu-end p-3 shadow" style="min-width: 300px; max-height: 420px; overflow-y: auto;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong class="small">Schools</strong>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-link btn-sm p-0" id="schoolFilterSelectAll">Select all</button>
                    <button type="button" class="btn btn-link btn-sm p-0" id="schoolFilterClearAll">Clear</button>
                </div>
            </div>
            @foreach ($schools as $school)
                <div class="form-check">
                    <input class="form-check-input school-filter-checkbox" type="checkbox" name="school_ids[]" value="{{ $school->id }}" id="school-filter-{{ $school->id }}"
                        {{ in_array($school->id, $schoolIds, true) ? 'checked' : '' }}>
                    <label class="form-check-label small" for="school-filter-{{ $school->id }}">{{ $school->name }}</label>
                </div>
            @endforeach
            <hr class="my-2">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-funnel"></i> Apply</button>
                @if ($schoolIds)
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-3">
        <x-kpi-card icon="bi-cash-stack" label="Gross Payroll Cost" :value="$fmt($totalEarnings)"
            :trend="$pct($grossTrendPct)" :trend-direction="$dir($grossTrendPct)"
            :caption="now()->format('F Y').' cycle'" />
    </div>
    <div class="col-md-6 col-lg-3">
        <x-kpi-card icon="bi-graph-up-arrow" label="Net Disbursement" :value="$fmt($netPayroll)"
            :trend="$pct($netTrendPct)" :trend-direction="$dir($netTrendPct)"
            :tone="$currentPendingCount === 0 ? 'success' : 'default'"
            :badge="$currentPendingCount === 0 ? 'On Track' : 'In Progress'"
            caption="After all deductions" />
    </div>
    <div class="col-md-6 col-lg-3">
        <x-kpi-card icon="bi-exclamation-circle" label="Pending Approvals" :value="$pendingCycles->count()"
            :tone="$pendingCycles->count() > 0 ? 'danger' : 'success'"
            :badge="$pendingCycles->count() > 0 ? 'Needs Action' : 'Clear'"
            :caption="$pendingCaption" />
    </div>
    <div class="col-md-6 col-lg-3">
        <x-kpi-card icon="bi-people" label="Employees Processed" value="{{ $employeesProcessed }} of {{ $totalEmployees }}"
            :trend="$newJoinersThisMonth > 0 ? '+'.$newJoinersThisMonth.' joined this month' : null" trend-direction="up"
            caption="{{ $teachingCount }} teaching · {{ $nonTeachingCount }} non-teaching" />
    </div>
    <div class="col-md-6 col-lg-3">
        <x-kpi-card icon="bi-activity" label="MoM Cost Variance"
            :value="$costVariance === null ? '—' : ($costVariance >= 0 ? '+' : '-').$fmt(abs($costVariance))"
            :tone="$costVariance !== null ? 'warning' : 'default'"
            :caption="$costVariance === null ? 'No data for '.now()->subMonthNoOverflow()->format('F Y').' to compare' : 'vs '.now()->subMonthNoOverflow()->format('F Y')" />
    </div>
    <div class="col-md-6 col-lg-3">
        <x-kpi-card icon="bi-clock-history" label="Avg. Approval Turnaround" value="{{ $turnaround }}"
            caption="Per payroll run (last 10)" />
    </div>
    <div class="col-md-6 col-lg-3">
        <x-kpi-card icon="bi-arrow-repeat" label="AIIMS Sync Health" value="{{ $lastSync ? ucfirst(str_replace('_', ' ', $lastSync->status)) : 'No sync yet' }}"
            :tone="$syncTone" :badge="$lastSync?->records_failed > 0 ? $lastSync->records_failed.' failed' : null"
            :caption="$lastSync ? 'Fetched '.$lastSync->records_fetched.' · '.$lastSync->started_at->diffForHumans() : null" />
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold">Payroll Cost Trend</div>
                    <div class="text-muted small">Last 12 months</div>
                </div>
            </div>
            <div class="card-body">
                <canvas id="costTrendChart" height="220"></canvas>
                <div class="d-flex gap-4 mt-3 pt-3 border-top small text-muted">
                    <div><div class="text-uppercase" style="font-size:.65rem;">YTD Gross</div><div class="fw-semibold text-dark">{{ $fmt($ytdGross) }}</div></div>
                    <div><div class="text-uppercase" style="font-size:.65rem;">YTD Net</div><div class="fw-semibold text-dark">{{ $fmt($ytdNet) }}</div></div>
                    <div><div class="text-uppercase" style="font-size:.65rem;">Avg. Headcount</div><div class="fw-semibold text-dark">{{ $avgHeadcount }}</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold">Dept. Payroll Cost</div>
                    <div class="text-muted small">{{ now()->format('F Y') }} &middot; Net by department</div>
                </div>
                <span class="badge text-bg-light border">{{ $deptBreakdown->count() }} depts</span>
            </div>
            <div class="card-body">
                @if ($deptBreakdown->isNotEmpty())
                    <canvas id="deptCostChart" height="220"></canvas>
                    <div class="text-muted small mt-2 pt-2 border-top">
                        {{ $deptBreakdown->first()['department'] }} represents {{ $topDeptShare }}% of total cost
                    </div>
                @else
                    <div class="text-muted text-center py-5">No payroll computed yet this month.</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold">Recent Payroll Runs</div>
                    <div class="text-muted small">Last {{ $recentRuns->count() }} cycles across all schools</div>
                </div>
                <a href="{{ route('payroll-cycles.index') }}" class="small">View all</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead><tr><th>Run</th><th class="text-end">Employees</th><th class="text-end">Gross</th><th class="text-end">Net</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse ($recentRuns as $run)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $run->school->name }} &mdash; {{ $run->label() }}</div>
                                <div class="text-muted small">{{ $run->creator?->name ?? 'System' }}</div>
                            </td>
                            <td class="text-end">{{ $run->items_count }}</td>
                            <td class="text-end"><x-money :value="$run->gross_sum ?? 0" :decimals="0" /></td>
                            <td class="text-end"><x-money :value="$run->net_sum ?? 0" :decimals="0" /></td>
                            <td><x-status-badge :status="$run->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted text-center py-3">No payroll cycles yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header">Recent Activity</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse ($recentActivity as $log)
                        <li class="list-group-item d-flex align-items-start gap-2">
                            <x-avatar :name="$log->user?->name ?? 'System'" size="sm" />
                            <div class="flex-grow-1">
                                <div class="small">
                                    <strong>{{ $log->user?->name ?? 'System' }}</strong>
                                    {{ $log->action }}
                                    {{ class_basename($log->auditable_type) }}
                                    <span class="text-muted">#{{ $log->auditable_id }}</span>
                                </div>
                                <div class="text-muted" style="font-size: .75rem;">{{ $log->created_at->diffForHumans() }}</div>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No activity recorded yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var selectAll = document.getElementById('schoolFilterSelectAll');
        var clearAll = document.getElementById('schoolFilterClearAll');
        var checkboxes = document.querySelectorAll('.school-filter-checkbox');

        if (selectAll) {
            selectAll.addEventListener('click', function () {
                checkboxes.forEach(function (cb) { cb.checked = true; });
            });
        }
        if (clearAll) {
            clearAll.addEventListener('click', function () {
                checkboxes.forEach(function (cb) { cb.checked = false; });
            });
        }

        new Chart(document.getElementById('costTrendChart'), {
            type: 'line',
            data: {
                labels: @json($trend['labels']),
                datasets: [
                    { label: 'Gross', data: @json($trend['gross']), borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.08)', fill: true, tension: .35, pointRadius: 0, borderWidth: 2 },
                    { label: 'Net', data: @json($trend['net']), borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,.08)', fill: true, tension: .35, pointRadius: 0, borderWidth: 2 },
                ],
            },
            options: {
                animation: false,
                plugins: { legend: { position: 'top', align: 'end', labels: { boxWidth: 10, usePointStyle: true, font: { size: 11 } } } },
                scales: { y: { ticks: { callback: (v) => '₹' + Number(v).toLocaleString('en-IN') } } },
            },
        });

        var deptCanvas = document.getElementById('deptCostChart');
        if (deptCanvas) {
            new Chart(deptCanvas, {
                type: 'bar',
                data: {
                    labels: @json($deptBreakdown->pluck('department')),
                    datasets: [{ data: @json($deptBreakdown->pluck('total')), backgroundColor: '#0d9488', borderRadius: 4, maxBarThickness: 22 }],
                },
                options: {
                    animation: false,
                    indexAxis: 'y',
                    plugins: { legend: { display: false } },
                    scales: { x: { ticks: { callback: (v) => '₹' + Number(v).toLocaleString('en-IN') } } },
                },
            });
        }
    });
</script>
@endpush
@endsection
