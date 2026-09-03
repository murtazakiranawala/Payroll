@extends('layouts.app')

@section('title', 'Employees')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-people"></i> Employees</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('employees.create') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-plus-lg"></i> Add Manually</a>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="POST" action="{{ route('employees.sync') }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-auto">
                <label class="form-label small mb-0">Sync from AIIMS Central ERP</label>
                <select name="school_id" class="form-select form-select-sm" required>
                    <option value="">Select school...</option>
                    @foreach ($schools as $school)
                        <option value="{{ $school->id }}">{{ $school->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto form-check mt-4">
                <input type="checkbox" name="full" value="1" class="form-check-input" id="full-sync">
                <label class="form-check-label small" for="full-sync">Full load (instead of incremental)</label>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary btn-sm"><i class="bi bi-arrow-repeat"></i> Sync now</button>
            </div>
        </form>
    </div>
</div>

<form method="GET" class="row g-2 mb-3">
    <div class="col-auto">
        <input type="text" name="q" class="form-control form-control-sm" placeholder="Search name or code" value="{{ request('q') }}">
    </div>
    <div class="col-auto">
        <select name="school_id" class="form-select form-select-sm">
            <option value="">All schools</option>
            @foreach ($schools as $school)
                <option value="{{ $school->id }}" {{ request('school_id') == $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <select name="status" class="form-select form-select-sm">
            <option value="">Any status</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="on_leave" {{ request('status') === 'on_leave' ? 'selected' : '' }}>On Leave</option>
            <option value="exited" {{ request('status') === 'exited' ? 'selected' : '' }}>Exited</option>
        </select>
    </div>
    <div class="col-auto"><button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i> Filter</button></div>
</form>

<div class="card shadow-sm">
    <table class="table table-hover mb-0 data-table-nopage">
        <thead><tr><th>Code</th><th>Name</th><th>School</th><th>Designation</th><th>Status</th><th>Grade</th><th>Source</th><th></th></tr></thead>
        <tbody>
        @foreach ($employees as $employee)
            <tr>
                <td>{{ $employee->external_employee_code ?? '—' }}</td>
                <td class="d-flex align-items-center gap-2"><x-avatar :name="$employee->name" size="sm" /> {{ $employee->name }}</td>
                <td>{{ $employee->school->name }}</td>
                <td>{{ $employee->designation }}</td>
                <td><x-status-badge :status="$employee->employment_status" /></td>
                <td>
                    @if ($employee->staffGrade)
                        {{ $employee->staffGrade->code }}
                        @php $compliance = $employee->salaryComplianceStatus(); @endphp
                        @if ($compliance && $compliance !== 'within_band')
                            <x-status-badge :status="$compliance" />
                        @endif
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td><span class="badge bg-light text-dark border">{{ $employee->source === 'aiims_sync' ? 'AIIMS' : 'Manual' }}</span></td>
                <td><a href="{{ route('employees.show', $employee) }}" class="small"><i class="bi bi-eye"></i> View</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $employees->links() }}</div>
@endsection
