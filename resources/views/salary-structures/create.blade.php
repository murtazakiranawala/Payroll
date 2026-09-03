@extends('layouts.app')

@section('title', 'Salary Structure - '.$employee->name)

@section('content')
<h4 class="mb-3"><i class="bi bi-diagram-3"></i> Salary Structure &mdash; {{ $employee->name }}</h4>

@if ($employee->staffGrade)
    @php $grade = $employee->staffGrade; @endphp
    <div class="alert alert-info py-2 small" style="max-width: 720px;">
        <strong>Grade {{ $grade->code }} &mdash; {{ $grade->description }}</strong>
        @if ($grade->min_basic !== null)
            &middot; Basic band: ₹{{ number_format((float) $grade->min_basic) }} &ndash; ₹{{ number_format((float) $grade->max_basic) }}
            &middot; Standard yearly increment: ₹{{ number_format((float) $grade->yearly_increment) }}
            @if (! $previousStructure)
                &middot; Max joining basic (policy &sect;6): ₹{{ number_format($grade->maxJoiningBasic()) }}
            @else
                &middot; Current basic: ₹{{ number_format((float) $previousStructure->basic) }}
                &middot; Max increment allowed (policy &sect;7): ₹{{ number_format($grade->maxAnnualIncrement()) }}
            @endif
        @else
            &middot; Pay band not yet finalized for this grade (TBA).
        @endif
    </div>
@endif

@if ($employee->school->hraPercent() !== null)
    <div class="alert alert-secondary py-2 small" style="max-width: 720px;">
        <strong>{{ \App\Models\School::TIER_LABELS[$employee->school->location_tier] }} location</strong>
        &middot; Policy HRA: {{ $employee->school->hraPercent() }}% of basic
        &middot; Policy CCA: {{ $employee->school->ccaPercent() }}% of basic
        <span class="text-muted">(pre-filled below, per Annexure B-2 &mdash; adjust if needed)</span>
    </div>
@endif

<div class="card shadow-sm" style="max-width: 720px;">
    <div class="card-body">
        <form method="POST" action="{{ route('salary-structures.store', $employee) }}">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Effective From</label>
                    <input type="date" name="effective_from" class="form-control" value="{{ old('effective_from', now()->toDateString()) }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">CTC (annual, optional)</label>
                    <input type="number" step="0.01" name="ctc" class="form-control" value="{{ old('ctc') }}">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Basic Salary (monthly)</label>
                <input type="number" step="0.01" name="basic" class="form-control" value="{{ old('basic') }}" required>
            </div>

            <hr>
            <h6>Additional Earning Components</h6>
            <p class="small text-muted">Enter either a fixed monthly amount or a percentage of basic for each component that applies.</p>
            @foreach ($components as $i => $component)
                <div class="row align-items-center mb-2">
                    <div class="col-md-4">
                        <input type="hidden" name="lines[{{ $i }}][salary_component_id]" value="{{ $component->id }}">
                        {{ $component->name }}
                    </div>
                    <div class="col-md-4">
                        <input type="number" step="0.01" name="lines[{{ $i }}][amount]" class="form-control form-control-sm" placeholder="Fixed amount">
                    </div>
                    <div class="col-md-4">
                        @php
                            $tierPercent = match ($component->code) {
                                'HRA' => $employee->school->hraPercent(),
                                'CCA' => $employee->school->ccaPercent(),
                                default => null,
                            };
                        @endphp
                        <input type="number" step="0.01" name="lines[{{ $i }}][percentage]" class="form-control form-control-sm" placeholder="% of basic" value="{{ $tierPercent ?? $component->default_percentage }}">
                    </div>
                </div>
            @endforeach

            <button class="btn btn-primary mt-3"><i class="bi bi-check-lg"></i> Save Salary Structure</button>
            <a href="{{ route('employees.show', $employee) }}" class="btn btn-link">Cancel</a>
        </form>
    </div>
</div>
@endsection
