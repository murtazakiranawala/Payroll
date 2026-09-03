@extends('layouts.app')

@section('title', 'Give Increment - '.$employee->name)

@section('content')
<h4 class="mb-3"><i class="bi bi-graph-up-arrow"></i> Give Increment &mdash; {{ $employee->name }}</h4>

@php $grade = $employee->staffGrade; @endphp
<div class="alert alert-info py-2 small" style="max-width: 720px;">
    <strong>Grade {{ $grade->code }} &mdash; {{ $grade->description }}</strong>
    &middot; Current basic: ₹{{ number_format((float) $previousStructure->basic) }}
    &middot; Standard yearly increment: ₹{{ number_format((float) $grade->yearly_increment) }}
</div>

<div class="card shadow-sm" style="max-width: 720px;">
    <div class="card-body">
        <form method="POST" action="{{ route('increments.store', $employee) }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Performance Rating</label>
                <select name="performance_rating" id="performance_rating" class="form-select" required>
                    <option value="">Select a rating&hellip;</option>
                    @foreach ([
                        'below_average' => 'Below Average — No increment',
                        'average' => 'Average — Standard increment (1×)',
                        'above_average' => 'Above Average — Double increment (2×)',
                    ] as $value => $label)
                        <option value="{{ $value }}" data-amount="{{ $grade->incrementFor($value) }}" {{ old('performance_rating') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="form-text">Per policy &sect;7: the quantum of increment is tied to the performance review outcome.</div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Effective From</label>
                    <input type="date" name="effective_from" class="form-control" value="{{ old('effective_from', now()->toDateString()) }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">New Basic</label>
                    <input type="text" id="new_basic_preview" class="form-control" value="—" readonly tabindex="-1">
                    <div class="form-text">Calculated automatically from the rating selected above.</div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Remarks (optional)</label>
                <textarea name="remarks" class="form-control" rows="2" placeholder="e.g. KRAs met, strong Q3 performance">{{ old('remarks') }}</textarea>
            </div>

            <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Apply Increment</button>
            <a href="{{ route('employees.show', $employee) }}" class="btn btn-link">Cancel</a>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var currentBasic = {{ (float) $previousStructure->basic }};
        var select = document.getElementById('performance_rating');
        var preview = document.getElementById('new_basic_preview');

        function updatePreview() {
            var option = select.options[select.selectedIndex];
            var amount = option ? parseFloat(option.getAttribute('data-amount')) : NaN;
            preview.value = isNaN(amount) ? '—' : '₹ ' + (currentBasic + amount).toLocaleString('en-IN', { maximumFractionDigits: 2 });
        }

        select.addEventListener('change', updatePreview);
        updatePreview();
    });
</script>
@endpush
@endsection
