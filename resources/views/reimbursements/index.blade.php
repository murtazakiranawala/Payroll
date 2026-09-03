@extends('layouts.app')

@section('title', 'Reimbursement Claims')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <h4 class="mb-0"><i class="bi bi-receipt"></i> Reimbursement Claims</h4>
    <a href="{{ route('reimbursements.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Submit Claim</a>
</div>

<div class="card shadow-sm">
    <table class="table table-hover mb-0 data-table-nopage">
        <thead><tr><th>Employee</th><th>Category</th><th>Amount</th><th>Claim Date</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @foreach ($claims as $claim)
            <tr>
                <td>{{ $claim->employee->name }} <span class="text-muted small">({{ $claim->employee->school->name }})</span></td>
                <td>{{ $claim->category }}</td>
                <td><x-money :value="$claim->amount" /></td>
                <td>{{ $claim->claim_date->format('d M Y') }}</td>
                <td><x-status-badge :status="$claim->status" /></td>
                <td>
                    @if ($claim->status === 'pending')
                        <form method="POST" action="{{ route('reimbursements.approve', $claim) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-success"><i class="bi bi-check-lg"></i> Approve</button></form>
                        <form method="POST" action="{{ route('reimbursements.reject', $claim) }}" class="d-inline" data-confirm="Reject this reimbursement claim?">@csrf<button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i> Reject</button></form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $claims->links() }}</div>
@endsection
