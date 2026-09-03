@extends('layouts.app')

@section('title', 'Salary Structures')

@section('content')
<h4 class="mb-3"><i class="bi bi-diagram-3"></i> Salary Structures</h4>

<form method="GET" class="row g-2 mb-3">
    <div class="col-auto">
        <select name="school_id" class="form-select form-select-sm">
            <option value="">All schools</option>
            @foreach ($schools as $school)
                <option value="{{ $school->id }}" {{ request('school_id') == $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto"><button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i> Filter</button></div>
</form>

<div class="card shadow-sm">
    <table class="table table-hover mb-0 data-table-nopage">
        <thead><tr><th>Employee</th><th>School</th><th>Grade</th><th>Current Basic</th><th>Effective From</th><th></th></tr></thead>
        <tbody>
        @foreach ($employees as $employee)
            @php $structure = $employee->salaryStructures->first(); @endphp
            <tr>
                <td>{{ $employee->name }}</td>
                <td>{{ $employee->school->name }}</td>
                <td>{{ $employee->staffGrade->code ?? '—' }}</td>
                <td>
                    @if($structure)
                        <x-money :value="$structure->basic" />
                        @php $compliance = $employee->salaryComplianceStatus(); @endphp
                        @if ($compliance && $compliance !== 'within_band')
                            <x-status-badge :status="$compliance" />
                        @endif
                    @else — @endif
                </td>
                <td>{{ $structure?->effective_from->format('d M Y') ?? '—' }}</td>
                <td><a href="{{ route('salary-structures.create', $employee) }}" class="small"><i class="bi bi-pencil"></i> {{ $structure ? 'Update' : 'Set up' }}</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $employees->links() }}</div>
@endsection
