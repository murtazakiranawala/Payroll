@extends('layouts.app')

@section('title', $employee->name)

@section('content')
<div class="d-flex justify-content-between align-items-start mb-3">
    <div class="d-flex align-items-center gap-3">
        <x-avatar :name="$employee->name" size="lg" />
        <div>
            <h4 class="mb-0">{{ $employee->name }}</h4>
            <div class="text-muted small">{{ $employee->designation }} &middot; {{ $employee->department }} &middot; {{ $employee->school->name }}</div>
        </div>
    </div>
    <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Edit</a>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card shadow-sm mb-3">
            <div class="card-header">Profile</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><th>Employee Code</th><td>{{ $employee->external_employee_code ?? '—' }}</td></tr>
                    <tr><th>Category</th><td>{{ str_replace('_',' ', $employee->category) }}</td></tr>
                    <tr><th>Status</th><td><x-status-badge :status="$employee->employment_status" /></td></tr>
                    <tr><th>Date of Joining</th><td>{{ $employee->date_of_joining?->format('d M Y') ?? '—' }}</td></tr>
                    <tr><th>Date of Exit</th><td>{{ $employee->date_of_exit?->format('d M Y') ?? '—' }}</td></tr>
                    <tr><th>Email</th><td>{{ $employee->email ?? '—' }}</td></tr>
                    <tr><th>Phone</th><td>{{ $employee->phone ?? '—' }}</td></tr>
                    <tr><th>Bank</th><td>{{ $employee->bank_name }} &middot; {{ $employee->maskedBankAccount() }} &middot; {{ $employee->bank_ifsc }}</td></tr>
                    <tr><th>PAN</th><td>{{ $employee->maskedPan() ?? '—' }}</td></tr>
                    <tr><th>UAN</th><td>{{ $employee->uan_number ?? '—' }}</td></tr>
                    <tr><th>Source</th><td>{{ $employee->source === 'aiims_sync' ? 'AIIMS sync (last: '.$employee->last_synced_at?->diffForHumans().')' : 'Manual entry' }}</td></tr>
                </table>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header d-flex justify-content-between">
                <span>Statutory Applicability</span>
                <a href="{{ route('employees.edit', $employee) }}" class="small"><i class="bi bi-pencil"></i> Edit</a>
            </div>
            <div class="card-body d-flex gap-2 flex-wrap">
                <span class="badge text-bg-{{ $employee->pf_applicable ? 'success' : 'secondary' }}">PF {{ $employee->pf_applicable ? 'Applicable' : 'Not Applicable' }}</span>
                <span class="badge text-bg-{{ $employee->esi_applicable ? 'success' : 'secondary' }}">ESI {{ $employee->esi_applicable ? 'Applicable' : 'Not Applicable' }}</span>
                <span class="badge text-bg-{{ $employee->pt_applicable ? 'success' : 'secondary' }}">PT {{ $employee->pt_applicable ? 'Applicable' : 'Not Applicable' }}</span>
                <span class="badge text-bg-{{ $employee->lwf_applicable ? 'success' : 'secondary' }}">LWF {{ $employee->lwf_applicable ? 'Applicable' : 'Not Applicable' }}</span>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Salary Structure</span>
                <div class="d-flex gap-3">
                    @if ($employee->staffGrade && $employee->staffGrade->yearly_increment !== null && $employee->salaryStructures->firstWhere('is_active', true))
                        <a href="{{ route('increments.create', $employee) }}" class="small"><i class="bi bi-graph-up-arrow"></i> Give increment</a>
                    @endif
                    <a href="{{ route('salary-structures.create', $employee) }}" class="small">Add new version</a>
                </div>
            </div>
            <div class="card-body">
                @if ($employee->staffGrade)
                    @php $compliance = $employee->salaryComplianceStatus(); @endphp
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-3 pb-3 border-bottom">
                        <span class="badge bg-light text-dark border">Grade {{ $employee->staffGrade->code }} &mdash; {{ $employee->staffGrade->description }}</span>
                        @if ($employee->staffGrade->min_basic !== null)
                            <span class="text-muted small">Band: <x-money :value="$employee->staffGrade->min_basic" :decimals="0" /> &ndash; <x-money :value="$employee->staffGrade->max_basic" :decimals="0" /></span>
                        @endif
                        @if ($compliance)
                            <x-status-badge :status="$compliance" />
                        @endif
                    </div>
                    @if ($compliance === 'below_min')
                        <div class="alert alert-warning py-2 small mb-3">Below the grade minimum. Policy &sect;10: bring this up to the minimum over the next 2 years via suitable increments.</div>
                    @elseif ($compliance === 'above_max')
                        <div class="alert alert-danger py-2 small mb-3">Above the grade maximum. Policy &sect;10: must be reported to the Idara with a suitable plan.</div>
                    @endif
                @endif
                @php $structure = $employee->salaryStructures->firstWhere('is_active', true); @endphp
                @if ($structure)
                    <div class="small text-muted mb-2">
                        Effective from {{ $structure->effective_from->format('d M Y') }} &middot;
                        CTC: @if($structure->ctc)<x-money :value="$structure->ctc" />@else &mdash; @endif
                        @if ($structure->performance_rating)
                            &middot; <span class="badge bg-light text-dark border">{{ \App\Models\EmployeeSalaryStructure::RATING_LABELS[$structure->performance_rating] }} review</span>
                        @endif
                    </div>
                    <table class="table table-sm mb-0">
                        <tr><th>Basic</th><td><x-money :value="$structure->basic" /></td></tr>
                        @foreach ($structure->lines as $line)
                            <tr>
                                <th>{{ $line->component->name }}</th>
                                <td>
                                    @if($line->percentage)
                                        {{ $line->percentage }}% of basic
                                    @else
                                        <x-money :value="$line->amount" />
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </table>
                @else
                    <div class="text-muted">No salary structure configured yet.</div>
                @endif
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header">Reimbursement Claims</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Date</th><th>Category</th><th>Amount</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse ($employee->reimbursementClaims->sortByDesc('claim_date') as $claim)
                        <tr>
                            <td>{{ $claim->claim_date->format('d M Y') }}</td>
                            <td>{{ $claim->category }}</td>
                            <td><x-money :value="$claim->amount" /></td>
                            <td><x-status-badge :status="$claim->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">No claims submitted.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header">Payroll History</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Cycle</th><th>Net Pay</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($employee->payrollItems->sortByDesc(fn($i) => $i->payrollCycle->year * 100 + $i->payrollCycle->month) as $item)
                        <tr>
                            <td>{{ $item->payrollCycle->label() }}</td>
                            <td class="net-pay-cell"><x-money :value="$item->net_pay" /></td>
                            <td><a href="{{ route('payslips.show', [$item->payrollCycle, $item]) }}" target="_blank" class="small"><i class="bi bi-file-earmark-text"></i> Payslip</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">No payroll history yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
