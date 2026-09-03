@extends('layouts.app')

@section('title', 'Reconciliation - '.$cycle->label())

@section('content')
<h4 class="mb-3">Reconciliation &mdash; {{ $cycle->school->name }} &middot; {{ $cycle->label() }}</h4>

@if ($cycle->reconciliationRecord)
    @php $rec = $cycle->reconciliationRecord; @endphp
    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="card card-metric shadow-sm"><div class="card-body">
            <div class="text-muted small">Payroll Register Total</div>
            <div class="value"><x-money :value="$rec->payroll_register_total" /></div>
        </div></div></div>
        <div class="col-md-4"><div class="card card-metric shadow-sm"><div class="card-body">
            <div class="text-muted small">Posted to Financial ERP</div>
            <div class="value"><x-money :value="$rec->financial_erp_posted_total" /></div>
        </div></div></div>
        <div class="col-md-4"><div class="card card-metric shadow-sm"><div class="card-body">
            <div class="text-muted small">Variance</div>
            <div class="value"><x-money :value="$rec->variance" /> <x-status-badge :status="$rec->status" class="fs-6 align-middle" /></div>
        </div></div></div>
    </div>
    <p class="text-muted small">Generated {{ $rec->generated_at->diffForHumans() }}</p>
@else
    <p class="text-muted">No reconciliation record yet. It becomes available once the journal voucher has been posted to the Financial ERP.</p>
@endif

<a href="{{ route('payroll-cycles.show', $cycle) }}" class="btn btn-sm btn-link"><i class="bi bi-arrow-left"></i> Back to cycle</a>
@endsection
