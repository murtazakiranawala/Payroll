@extends('layouts.app')

@section('title', 'GL Account Mappings')

@section('content')
<h4 class="mb-3"><i class="bi bi-link-45deg"></i> GL Account Mappings</h4>
<p class="text-muted small">Maps each payroll cost/liability category to a Financial ERP GL account (and optional cost centre). A school-specific mapping overrides the global default for that category. Required before a journal voucher can be generated (BRD FR-3.2).</p>

<div class="row">
    <div class="col-md-7">
        <div class="card shadow-sm">
            <table class="table table-sm mb-0 data-table">
                <thead><tr><th>Category</th><th>School</th><th>GL Account</th><th>Cost Centre</th><th></th></tr></thead>
                <tbody>
                @foreach ($mappings as $mapping)
                    <tr>
                        <td>{{ str_replace('_', ' ', $mapping->category) }}</td>
                        <td>{{ $mapping->school->name ?? 'Default (all schools)' }}</td>
                        <td>{{ $mapping->gl_account_code }}</td>
                        <td>{{ $mapping->cost_centre_code ?? '—' }}</td>
                        <td>
                            <form method="POST" action="{{ route('gl-mappings.destroy', $mapping) }}" data-confirm="Remove this mapping?">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Remove</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6><i class="bi bi-plus-lg"></i> Add / Update Mapping</h6>
                <form method="POST" action="{{ route('gl-mappings.store') }}">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label small">Category</label>
                        <select name="category" class="form-select form-select-sm" required>
                            @foreach ($categories as $category)
                                <option value="{{ $category }}">{{ str_replace('_', ' ', $category) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">School (blank = global default)</label>
                        <select name="school_id" class="form-select form-select-sm">
                            <option value="">Default (all schools)</option>
                            @foreach ($schools as $school)
                                <option value="{{ $school->id }}">{{ $school->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">GL Account Code</label>
                        <input type="text" name="gl_account_code" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Cost Centre Code</label>
                        <input type="text" name="cost_centre_code" class="form-control form-control-sm">
                    </div>
                    <button class="btn btn-primary btn-sm"><i class="bi bi-check-lg"></i> Save Mapping</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
