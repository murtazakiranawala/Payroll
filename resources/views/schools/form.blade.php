@extends('layouts.app')

@section('title', $school->exists ? 'Edit School' : 'Add School')

@section('content')
<h4 class="mb-3"><i class="bi bi-building"></i> {{ $school->exists ? 'Edit School' : 'Add School' }}</h4>

<form method="POST" action="{{ $school->exists ? route('schools.update', $school) : route('schools.store') }}" style="max-width: 680px;">
    @csrf
    @if ($school->exists) @method('PUT') @endif

    <div class="card shadow-sm mb-3">
        <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-info-circle text-muted"></i> Basic Details</div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $school->name) }}" required>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Code</label>
                    <input type="text" name="code" class="form-control" value="{{ old('code', $school->code) }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">AIIMS School Code</label>
                    <input type="text" name="aiims_school_code" class="form-control" value="{{ old('aiims_school_code', $school->aiims_school_code) }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-8 mb-0">
                    <label class="form-label">Address</label>
                    <input type="text" name="address" class="form-control" value="{{ old('address', $school->address) }}">
                </div>
                <div class="col-md-4 mb-0">
                    <label class="form-label">Location Tier</label>
                    <select name="location_tier" class="form-select">
                        <option value="">Not set</option>
                        @foreach (\App\Models\School::TIER_LABELS as $value => $label)
                            <option value="{{ $value }}" {{ old('location_tier', $school->location_tier) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Drives HRA/CCA % (policy Annexure B-2).</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-link-45deg text-muted"></i> Accounting &amp; Contact</div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Default GL Cost Centre Code</label>
                <input type="text" name="gl_cost_centre_code" class="form-control" value="{{ old('gl_cost_centre_code', $school->gl_cost_centre_code) }}">
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Contact Email</label>
                    <input type="email" name="contact_email" class="form-control" value="{{ old('contact_email', $school->contact_email) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Contact Phone</label>
                    <input type="text" name="contact_phone" class="form-control" value="{{ old('contact_phone', $school->contact_phone) }}">
                </div>
            </div>
            <div class="form-check mb-0">
                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $school->is_active ?? true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Active</label>
            </div>
        </div>
    </div>

    <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Save</button>
    <a href="{{ route('schools.index') }}" class="btn btn-link">Cancel</a>
</form>
@endsection
