@extends('layouts.app')

@section('title', 'Submit Reimbursement Claim')

@section('content')
<div class="card shadow-sm" style="max-width: 560px;">
    <div class="card-body">
        <h5 class="mb-3"><i class="bi bi-receipt"></i> Submit Reimbursement Claim</h5>
        <form method="POST" action="{{ route('reimbursements.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Employee</label>
                <select name="employee_id" class="form-select" required>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Category</label>
                <input type="text" name="category" class="form-control" placeholder="e.g. Travel, Medical" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <input type="text" name="description" class="form-control">
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" name="amount" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Claim Date</label>
                    <input type="date" name="claim_date" class="form-control" value="{{ now()->toDateString() }}" required>
                </div>
            </div>
            <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Submit</button>
            <a href="{{ route('reimbursements.index') }}" class="btn btn-link">Cancel</a>
        </form>
    </div>
</div>
@endsection
