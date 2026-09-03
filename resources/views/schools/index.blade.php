@extends('layouts.app')

@section('title', 'Schools')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <h4 class="mb-0"><i class="bi bi-building"></i> Schools</h4>
    <a href="{{ route('schools.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add School</a>
</div>

<div class="card shadow-sm">
    <table class="table table-hover mb-0 data-table">
        <thead><tr><th>Name</th><th>Code</th><th>AIIMS Code</th><th>Tier</th><th>Employees</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @foreach ($schools as $school)
            <tr>
                <td>{{ $school->name }}</td>
                <td>{{ $school->code }}</td>
                <td>{{ $school->aiims_school_code ?? '—' }}</td>
                <td>{{ \App\Models\School::TIER_LABELS[$school->location_tier] ?? '—' }}</td>
                <td>{{ $school->employees_count }}</td>
                <td><x-status-badge :status="$school->is_active ? 'active' : 'inactive'" /></td>
                <td><a href="{{ route('schools.edit', $school) }}" class="small"><i class="bi bi-pencil"></i> Edit</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
