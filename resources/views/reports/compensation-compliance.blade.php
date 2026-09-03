@extends('layouts.app')

@section('title', 'Compensation Compliance')

@section('content')
<h4 class="mb-3"><i class="bi bi-shield-exclamation"></i> Compensation Compliance</h4>
<p class="text-muted small">
    Employees whose current basic salary falls outside their assigned grade's policy band (policy &sect;10).
    <strong>Below minimum:</strong> plan to reach the minimum within 2 years via suitable increments.
    <strong>Above maximum:</strong> must be reported to the Idara with a suitable plan.
</p>

<div class="card shadow-sm">
    <table class="table table-hover mb-0 data-table-nopage">
        <thead>
            <tr>
                <th>Employee</th><th>School</th><th>Grade</th>
                <th class="text-end">Current Basic</th><th class="text-end">Grade Band</th><th>Status</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($employees as $employee)
            @php
                $structure = $employee->salaryStructures->firstWhere('is_active', true);
                $grade = $employee->staffGrade;
            @endphp
            <tr>
                <td class="d-flex align-items-center gap-2"><x-avatar :name="$employee->name" size="sm" /> {{ $employee->name }}</td>
                <td>{{ $employee->school->name }}</td>
                <td>{{ $grade->code }} &mdash; {{ $grade->description }}</td>
                <td class="text-end"><x-money :value="$structure->basic" :decimals="0" /></td>
                <td class="text-end">₹{{ number_format((float) $grade->min_basic) }} &ndash; ₹{{ number_format((float) $grade->max_basic) }}</td>
                <td><x-status-badge :status="$employee->salaryComplianceStatus()" /></td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-muted text-center py-4">Every graded employee is currently within their policy band.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
