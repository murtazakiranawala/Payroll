@extends('layouts.app')

@section('title', $employee->exists ? 'Edit Employee' : 'Add Employee')

@section('content')
<h4 class="mb-3">
    <i class="bi bi-person-{{ $employee->exists ? 'gear' : 'plus' }}"></i>
    {{ $employee->exists ? 'Edit Employee' : 'Add Employee' }}
</h4>

<form method="POST" action="{{ $employee->exists ? route('employees.update', $employee) : route('employees.store') }}" style="max-width: 820px;">
    @csrf
    @if ($employee->exists) @method('PUT') @endif

    <div class="card shadow-sm mb-3">
        <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-person-vcard text-muted"></i> Identity &amp; Employment</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">School</label>
                    <select name="school_id" class="form-select" required>
                        @foreach ($schools as $school)
                            <option value="{{ $school->id }}" {{ old('school_id', $employee->school_id) == $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Employee Code (if any)</label>
                    <input type="text" name="external_employee_code" class="form-control" value="{{ old('external_employee_code', $employee->external_employee_code) }}">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $employee->name) }}" required>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Designation</label>
                    <input type="text" name="designation" class="form-control" value="{{ old('designation', $employee->designation) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Department</label>
                    <input type="text" name="department" class="form-control" value="{{ old('department', $employee->department) }}">
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        @foreach (['teaching','non_teaching','administrative','support','other'] as $cat)
                            <option value="{{ $cat }}" {{ old('category', $employee->category) === $cat ? 'selected' : '' }}>{{ str_replace('_',' ', $cat) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Employment Status</label>
                    <select name="employment_status" class="form-select">
                        @foreach (['active','on_leave','exited'] as $st)
                            <option value="{{ $st }}" {{ old('employment_status', $employee->employment_status) === $st ? 'selected' : '' }}>{{ str_replace('_',' ', $st) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Staff Grade</label>
                    <select name="staff_grade_id" class="form-select">
                        <option value="">Not graded yet</option>
                        @foreach (['teaching' => 'Teaching', 'administrative' => 'Administrative', 'management' => 'Management'] as $type => $label)
                            <optgroup label="{{ $label }}">
                                @foreach ($staffGrades->where('staff_type', $type) as $grade)
                                    <option value="{{ $grade->id }}" {{ (string) old('staff_grade_id', $employee->staff_grade_id) === (string) $grade->id ? 'selected' : '' }}>
                                        {{ $grade->code }} &mdash; {{ $grade->description }}{{ $grade->applicable_to ? ' ('.$grade->applicable_to.')' : '' }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <div class="form-text">Determines the basic salary band and yearly increment cap per the Staff Grading &amp; Compensation Policy. <a href="{{ route('staff-grades.index') }}" target="_blank">View all grades</a>.</div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Date of Joining</label>
                    <input type="date" name="date_of_joining" class="form-control" value="{{ old('date_of_joining', $employee->date_of_joining?->toDateString()) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Date of Exit</label>
                    <input type="date" name="date_of_exit" class="form-control" value="{{ old('date_of_exit', $employee->date_of_exit?->toDateString()) }}">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $employee->email) }}">
                </div>
                <div class="col-md-6 mb-0">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $employee->phone) }}">
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-bank text-muted"></i> Bank &amp; Statutory</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Bank Account Number</label>
                    <input type="text" name="bank_account_number" class="form-control" value="{{ old('bank_account_number') }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">IFSC</label>
                    <input type="text" name="bank_ifsc" class="form-control" value="{{ old('bank_ifsc', $employee->bank_ifsc) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Bank Name</label>
                    <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $employee->bank_name) }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">PAN</label>
                    <input type="text" name="pan" class="form-control" value="{{ old('pan') }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">UAN Number</label>
                    <input type="text" name="uan_number" class="form-control" value="{{ old('uan_number', $employee->uan_number) }}">
                </div>
                <div class="col-md-4 mb-0">
                    <label class="form-label">ESI Number</label>
                    <input type="text" name="esi_number" class="form-control" value="{{ old('esi_number', $employee->esi_number) }}">
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-percent text-muted"></i> Statutory Applicability</div>
        <div class="card-body">
            <p class="text-muted small mb-3">Controls which statutory deductions this employee's payroll includes. The applicable rates/slabs themselves are configured under Statutory Rates (per school where set).</p>
            <div class="row">
                @foreach ([
                    'pf_applicable' => 'PF Applicable',
                    'esi_applicable' => 'ESI Applicable',
                    'pt_applicable' => 'PT Applicable',
                    'lwf_applicable' => 'LWF Applicable',
                ] as $field => $label)
                    <div class="col-md-3 mb-2">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="{{ $field }}" value="1" class="form-check-input" id="{{ $field }}"
                                {{ old($field, $employee->$field ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="{{ $field }}">{{ $label }}</label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Save</button>
    <a href="{{ route('employees.index') }}" class="btn btn-link">Cancel</a>
</form>
@endsection
