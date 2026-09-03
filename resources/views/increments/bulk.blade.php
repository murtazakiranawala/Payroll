@extends('layouts.app')

@section('title', 'Bulk Increments')

@section('content')
<h4 class="mb-3"><i class="bi bi-graph-up-arrow"></i> Bulk Increments</h4>
<p class="text-muted small">
    Set a performance rating per employee and apply them all together as one increment cycle. Leave a row on
    <strong>No change</strong> to skip it &mdash; only rows with a rating selected will get a new salary structure.
    Only active employees with a staff grade and a finalized increment amount are listed.
</p>

<form method="GET" class="row g-2 mb-3">
    <div class="col-auto">
        <select name="school_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All schools</option>
            @foreach ($schools as $school)
                <option value="{{ $school->id }}" {{ request('school_id') == $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
            @endforeach
        </select>
    </div>
</form>

@if ($employees->isEmpty())
    <div class="card shadow-sm"><div class="card-body text-muted text-center py-4">No eligible employees found for this filter.</div></div>
@else
    <form method="POST" action="{{ route('increments.bulk-store') }}">
        @csrf
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <label class="form-label">Effective From (applies to every increment below)</label>
                <input type="date" name="effective_from" class="form-control" style="max-width: 220px;" value="{{ now()->toDateString() }}" required>
            </div>
        </div>

        <div class="card shadow-sm">
            <table class="table table-hover mb-0 data-table-nopage">
                <thead>
                    <tr>
                        <th>Employee</th><th>School</th><th>Grade</th>
                        <th class="text-end">Current Basic</th><th class="text-end">Standard Increment</th>
                        <th>Performance Rating</th><th class="text-end">New Basic</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($employees as $employee)
                    @php
                        $grade = $employee->staffGrade;
                        $structure = $employee->salaryStructures->firstWhere('is_active', true);
                    @endphp
                    <tr>
                        <td class="d-flex align-items-center gap-2"><x-avatar :name="$employee->name" size="sm" /> {{ $employee->name }}</td>
                        <td>{{ $employee->school->name }}</td>
                        <td>{{ $grade->code }}</td>
                        <td class="text-end"><x-money :value="$structure->basic" :decimals="0" /></td>
                        <td class="text-end">₹{{ number_format((float) $grade->yearly_increment) }}</td>
                        <td>
                            <select name="ratings[{{ $employee->id }}]" class="form-select form-select-sm rating-select" data-basic="{{ (float) $structure->basic }}" data-increment="{{ (float) $grade->yearly_increment }}">
                                <option value="">No change</option>
                                <option value="below_average">Below Average (+₹0)</option>
                                <option value="average">Average (+₹{{ number_format((float) $grade->yearly_increment) }})</option>
                                <option value="above_average">Above Average (+₹{{ number_format(2 * (float) $grade->yearly_increment) }})</option>
                            </select>
                        </td>
                        <td class="text-end new-basic-cell">&mdash;</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <button class="btn btn-primary mt-3"><i class="bi bi-check-lg"></i> Apply Increments</button>
    </form>
@endif

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.rating-select').forEach(function (select) {
            var cell = select.closest('tr').querySelector('.new-basic-cell');
            var basic = parseFloat(select.getAttribute('data-basic'));
            var increment = parseFloat(select.getAttribute('data-increment'));

            function update() {
                var multiplier = { '': null, below_average: 0, average: 1, above_average: 2 }[select.value];
                cell.textContent = multiplier === null ? '—' : '₹ ' + (basic + multiplier * increment).toLocaleString('en-IN', { maximumFractionDigits: 2 });
            }

            select.addEventListener('change', update);
            update();
        });
    });
</script>
@endpush
@endsection
