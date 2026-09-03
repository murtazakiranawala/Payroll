@extends('layouts.app')

@section('title', $config->exists ? 'Edit Statutory Rate' : 'Add Statutory Rate')

@section('content')
<div class="card shadow-sm" style="max-width: 760px;">
    <div class="card-body">
        <h5 class="mb-3"><i class="bi bi-percent"></i> {{ $config->exists ? 'Edit' : 'Add' }} Statutory Rate Configuration</h5>

        <div class="alert alert-light border small">
            Expected JSON shape by type:
            <ul class="mb-0">
                <li><strong>PF</strong>: <code>{"employee_rate":12,"employer_rate":12,"wage_ceiling":15000,"eps_rate":8.33,"edli_rate":0.5}</code> (eps_rate/edli_rate are optional, default 0)</li>
                <li><strong>ESI</strong>: <code>{"employee_rate":0.75,"employer_rate":3.25,"wage_ceiling":21000}</code></li>
                <li><strong>PT</strong>: <code>{"slabs":[{"min":0,"max":7500,"amount":0},{"min":7501,"max":10000,"amount":175},{"min":10001,"max":null,"amount":200}]}</code></li>
                <li><strong>LWF</strong>: <code>{"employee_amount":10,"employer_amount":30}</code></li>
                <li><strong>TDS</strong>: <code>{"annual_exemption":250000,"slabs":[{"max":250000,"rate":0},{"max":500000,"rate":5},{"max":1000000,"rate":20},{"max":null,"rate":30}]}</code></li>
            </ul>
        </div>

        <form method="POST" action="{{ $config->exists ? route('statutory-rates.update', $config) : route('statutory-rates.store') }}">
            @csrf
            @if ($config->exists) @method('PUT') @endif

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select" required>
                        @foreach (['PF','ESI','TDS','PT','LWF'] as $type)
                            <option value="{{ $type }}" {{ old('type', $config->type) === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">School</label>
                    <select name="school_id" class="form-select">
                        <option value="">Default (all schools)</option>
                        @foreach ($schools as $school)
                            <option value="{{ $school->id }}" {{ (string) old('school_id', $config->school_id) === (string) $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Leave as "Default" for an org-wide rate; pick a school to override it just for that school.</div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $config->name) }}" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Configuration (JSON)</label>
                <textarea name="config_json" class="form-control" rows="4" required>{{ old('config_json', $config->config ? json_encode($config->config) : '') }}</textarea>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Effective From</label>
                    <input type="date" name="effective_from" class="form-control" value="{{ old('effective_from', $config->effective_from?->toDateString()) }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Effective To (optional)</label>
                    <input type="date" name="effective_to" class="form-control" value="{{ old('effective_to', $config->effective_to?->toDateString()) }}">
                </div>
            </div>

            <div class="form-check mb-3">
                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $config->is_active ?? true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Active</label>
            </div>

            <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Save</button>
            <a href="{{ route('statutory-rates.index') }}" class="btn btn-link">Cancel</a>
        </form>
    </div>
</div>
@endsection
