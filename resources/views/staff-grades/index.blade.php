@extends('layouts.app')

@section('title', 'Staff Grades')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <h4 class="mb-0"><i class="bi bi-bar-chart-steps"></i> Staff Grades</h4>
    <a href="{{ route('staff-grades.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add Grade</a>
</div>
<p class="text-muted small">
    Basic salary bands and standard yearly increment per grade, from the Staff Grading &amp; Compensation Policy.
    A new recruit's joining basic may exceed the minimum by up to 4 standard increments (policy &sect;6); the largest
    sanctioned annual increment is 2&times; the standard increment, for above-average performance (policy &sect;7).
</p>

@foreach (['teaching' => 'Teaching Staff', 'administrative' => 'Administrative Staff', 'management' => 'Management'] as $type => $label)
    @php $rows = $grades->where('staff_type', $type); @endphp
    @if ($rows->isNotEmpty())
        <div class="card shadow-sm mb-3">
            <div class="card-header">{{ $label }}</div>
            <table class="table table-hover mb-0 data-table-nopage">
                <thead>
                    <tr>
                        <th>Grade</th><th>Description</th><th>Applicable To</th>
                        <th class="text-end">Min Basic</th><th class="text-end">Max Basic</th>
                        <th class="text-end">Yearly Increment</th><th class="text-end">Max Joining (Min + 4 Inc.)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($rows as $grade)
                    <tr>
                        <td><span class="badge bg-light text-dark border">{{ $grade->code }}</span></td>
                        <td>{{ $grade->description }}</td>
                        <td>{{ $grade->applicable_to ?? '—' }}</td>
                        <td class="text-end">{{ $grade->min_basic !== null ? '₹ '.number_format((float) $grade->min_basic) : 'TBA' }}</td>
                        <td class="text-end">{{ $grade->max_basic !== null ? '₹ '.number_format((float) $grade->max_basic) : 'TBA' }}</td>
                        <td class="text-end">{{ $grade->yearly_increment !== null ? '₹ '.number_format((float) $grade->yearly_increment) : 'TBA' }}</td>
                        <td class="text-end">{{ $grade->maxJoiningBasic() !== null ? '₹ '.number_format($grade->maxJoiningBasic()) : '—' }}</td>
                        <td><a href="{{ route('staff-grades.edit', $grade) }}" class="small"><i class="bi bi-pencil"></i> Edit</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endforeach
@endsection
