@extends('layouts.app')

@section('title', 'New Payroll Cycle')

@section('content')
<div class="card shadow-sm" style="max-width: 480px;">
    <div class="card-body">
        <h5 class="mb-3"><i class="bi bi-calendar2-plus"></i> New Payroll Cycle</h5>
        <form method="POST" action="{{ route('payroll-cycles.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">School</label>
                <select name="school_id" class="form-select" required>
                    @foreach ($schools as $school)
                        <option value="{{ $school->id }}">{{ $school->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Month</label>
                    <select name="month" class="form-select" required>
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ now()->month == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create(2000, $m, 1)->format('F') }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Year</label>
                    <input type="number" name="year" class="form-control" value="{{ now()->year }}" required>
                </div>
            </div>
            <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Create Cycle</button>
            <a href="{{ route('payroll-cycles.index') }}" class="btn btn-link">Cancel</a>
        </form>
    </div>
</div>
@endsection
