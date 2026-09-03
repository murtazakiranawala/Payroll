@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<h4 class="mb-3"><i class="bi bi-bar-chart-line"></i> Reports</h4>

<div class="card shadow-sm mb-4">
    <div class="card-header">Employee Sync Log</div>
    <div class="card-body">
        <a href="{{ route('reports.sync-log') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-repeat"></i> View AIIMS Sync Log &amp; Exceptions</a>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header">Compensation Compliance</div>
    <div class="card-body">
        <a href="{{ route('reports.compensation-compliance') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-shield-exclamation"></i> View Employees Outside Grade Band</a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Per-Cycle Reports</div>
    <table class="table table-sm mb-0">
        <thead><tr><th>School</th><th>Period</th><th>Status</th><th colspan="4">Reports</th></tr></thead>
        <tbody>
        @foreach ($cycles as $cycle)
            <tr>
                <td>{{ $cycle->school->name }}</td>
                <td>{{ $cycle->label() }}</td>
                <td><x-status-badge :status="$cycle->status" /></td>
                <td><a href="{{ route('reports.salary-register', $cycle) }}" class="small">Salary Register</a></td>
                <td><a href="{{ route('reports.department-wise', $cycle) }}" class="small">Dept-wise</a></td>
                <td>
                    @foreach (['PF','ESI','TDS','PT','LWF'] as $type)
                        <a href="{{ route('reports.statutory', [$cycle, $type]) }}" class="small me-1">{{ $type }}</a>
                    @endforeach
                </td>
                <td><a href="{{ route('reports.reconciliation', $cycle) }}" class="small">Reconciliation</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
