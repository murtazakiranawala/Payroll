@extends('layouts.app')

@section('title', 'Statutory Rate Configuration')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <h4 class="mb-0"><i class="bi bi-percent"></i> Statutory Rate Configuration</h4>
    <a href="{{ route('statutory-rates.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add Configuration</a>
</div>
<p class="text-muted small">BRD FR-2.2: configurable, rule-based rates/slabs for PF, ESI, TDS, PT and LWF. Only one active config per type applies for a given date (the one with the latest effective_from &le; that date).</p>

<div class="card shadow-sm">
    <table class="table table-hover mb-0 data-table">
        <thead><tr><th>Type</th><th>School</th><th>Name</th><th>Effective From</th><th>Effective To</th><th>Active</th><th></th></tr></thead>
        <tbody>
        @foreach ($configs as $config)
            <tr>
                <td><span class="badge text-bg-info">{{ $config->type }}</span></td>
                <td>{{ $config->school->name ?? 'Default (all schools)' }}</td>
                <td>{{ $config->name }}</td>
                <td>{{ $config->effective_from->format('d M Y') }}</td>
                <td>{{ $config->effective_to?->format('d M Y') ?? '—' }}</td>
                <td><x-status-badge :status="$config->is_active ? 'active' : 'inactive'" /></td>
                <td><a href="{{ route('statutory-rates.edit', $config) }}" class="small"><i class="bi bi-pencil"></i> Edit</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
