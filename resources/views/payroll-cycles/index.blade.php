@extends('layouts.app')

@section('title', 'Payroll Cycles')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <h4 class="mb-0"><i class="bi bi-calendar2-check"></i> Payroll Cycles</h4>
    <a href="{{ route('payroll-cycles.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> New Cycle</a>
</div>

<div class="card shadow-sm">
    <table class="table table-hover mb-0 data-table-nopage">
        <thead><tr><th>School</th><th>Period</th><th>Status</th><th>Employees</th><th>Net Payroll</th><th></th></tr></thead>
        <tbody>
        @foreach ($cycles as $cycle)
            <tr>
                <td>{{ $cycle->school->name }}</td>
                <td>{{ $cycle->label() }}</td>
                <td><x-status-badge :status="$cycle->status" /></td>
                <td>{{ $cycle->items()->count() }}</td>
                <td><x-money :value="$cycle->items()->sum('net_pay')" /></td>
                <td><a href="{{ route('payroll-cycles.show', $cycle) }}" class="small"><i class="bi bi-box-arrow-up-right"></i> Open</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $cycles->links() }}</div>
@endsection
