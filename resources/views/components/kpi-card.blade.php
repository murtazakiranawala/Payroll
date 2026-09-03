{{--
    Dashboard summary card: icon + uppercase label + big value + optional
    trend line + optional caption + optional status pill, with a background
    tint driven by "tone" so at-a-glance severity reads before any numbers
    are read (matches the reference dashboard's card language).

    Usage: <x-kpi-card icon="bi-cash-coin" label="Gross Payroll Cost" value="Rs. 41,82,650"
               trend="+3.2% vs last month" trend-direction="up" caption="August 2026 cycle" />
           <x-kpi-card icon="bi-exclamation-circle" label="Pending Approvals" value="2"
               tone="danger" badge="Needs Action" caption="..." />
--}}
@props([
    'icon',
    'label',
    'value',
    'trend' => null,
    'trendDirection' => 'neutral',
    'caption' => null,
    'tone' => 'default',
    'badge' => null,
])
@php
    $toneBg = match ($tone) {
        'success' => '#ECFDF5',
        'warning' => '#FFFBEB',
        'danger' => '#FEF2F2',
        default => '#FFFFFF',
    };
    $toneIconColor = match ($tone) {
        'success' => '#047857',
        'warning' => '#B45309',
        'danger' => '#B91C1C',
        default => '#5B6B84',
    };
    $badgeClass = match ($tone) {
        'success' => 'text-bg-success',
        'warning' => 'text-bg-warning',
        'danger' => 'text-bg-danger',
        default => 'text-bg-secondary',
    };
    $trendColor = match ($trendDirection) {
        'up' => '#047857',
        'down' => '#B91C1C',
        default => '#5B6B84',
    };
    $trendIcon = match ($trendDirection) {
        'up' => 'bi-arrow-up-right',
        'down' => 'bi-arrow-down-right',
        default => 'bi-dash',
    };
@endphp
<div {{ $attributes->merge(['class' => 'card kpi-card shadow-sm h-100']) }} style="background-color: {{ $toneBg }};">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <i class="bi {{ $icon }}" style="font-size: 1.15rem; color: {{ $toneIconColor }};"></i>
            @if ($badge)
                <span class="badge rounded-pill {{ $badgeClass }}" style="font-size: .68rem;">{{ $badge }}</span>
            @endif
        </div>
        <div class="kpi-label">{{ $label }}</div>
        <div class="kpi-value">{{ $value }}</div>
        @if ($trend)
            <div class="small mt-1" style="color: {{ $trendColor }};"><i class="bi {{ $trendIcon }}"></i> {{ $trend }}</div>
        @endif
        @if ($caption)
            <div class="text-muted mt-1 kpi-caption">{{ $caption }}</div>
        @endif
    </div>
</div>
