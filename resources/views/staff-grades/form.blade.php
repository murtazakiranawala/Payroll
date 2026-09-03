@extends('layouts.app')

@section('title', $grade->exists ? 'Edit Staff Grade' : 'Add Staff Grade')

@section('content')
<h4 class="mb-3">
    <i class="bi bi-bar-chart-steps"></i>
    {{ $grade->exists ? 'Edit Staff Grade' : 'Add Staff Grade' }}
</h4>

<div class="card shadow-sm" style="max-width: 720px;">
    <div class="card-body">
        <form method="POST" action="{{ $grade->exists ? route('staff-grades.update', $grade) : route('staff-grades.store') }}">
            @csrf
            @if ($grade->exists) @method('PUT') @endif

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Grade Code</label>
                    <input type="text" name="code" class="form-control" value="{{ old('code', $grade->code) }}" placeholder="e.g. T3-A" required>
                </div>
                <div class="col-md-8 mb-3">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-control" value="{{ old('description', $grade->description) }}" placeholder="e.g. Teacher" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Applicable To</label>
                    <input type="text" name="applicable_to" class="form-control" value="{{ old('applicable_to', $grade->applicable_to) }}" placeholder="e.g. Primary, Office Staff">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Staff Type</label>
                    <select name="staff_type" class="form-select" required>
                        @foreach (['teaching' => 'Teaching', 'administrative' => 'Administrative', 'management' => 'Management'] as $value => $label)
                            <option value="{{ $value }}" {{ old('staff_type', $grade->staff_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <hr>
            <h6>Monthly Basic Salary Band</h6>
            <p class="small text-muted">Leave blank if not yet finalized for this grade.</p>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Minimum</label>
                    <input type="number" step="0.01" name="min_basic" class="form-control" value="{{ old('min_basic', $grade->min_basic) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Maximum</label>
                    <input type="number" step="0.01" name="max_basic" class="form-control" value="{{ old('max_basic', $grade->max_basic) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Yearly Increment</label>
                    <input type="number" step="0.01" name="yearly_increment" class="form-control" value="{{ old('yearly_increment', $grade->yearly_increment) }}">
                </div>
            </div>

            <button class="btn btn-primary mt-2"><i class="bi bi-check-lg"></i> Save</button>
            <a href="{{ route('staff-grades.index') }}" class="btn btn-link">Cancel</a>
        </form>
    </div>
</div>
@endsection
