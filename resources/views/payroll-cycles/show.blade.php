@extends('layouts.app')

@section('title', 'Payroll Cycle - '.$cycle->label())

@section('content')
<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
        <h4 class="mb-1">{{ $cycle->school->name }} &mdash; {{ $cycle->label() }}</h4>
        <x-status-badge :status="$cycle->status" />
        @if ($cycle->rejected_reason && $cycle->status === 'draft')
            <span class="text-danger small ms-2"><i class="bi bi-exclamation-circle"></i> Last rejected: {{ $cycle->rejected_reason }}</span>
        @endif
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @if ($cycle->status === 'draft')
            <form method="POST" action="{{ route('payroll-cycles.compute', $cycle) }}" @if($cycle->items->isNotEmpty()) data-confirm="Recompute payroll for this cycle? Any manual adjustments already saved (LOP, OT/bonus/arrears, wage overrides) are kept, but all computed amounts will be refreshed." @endif>
                @csrf
                <button class="btn btn-sm btn-primary">
                    <i class="bi bi-{{ $cycle->items->isEmpty() ? 'play-fill' : 'arrow-repeat' }}"></i>
                    {{ $cycle->items->isEmpty() ? 'Run Payroll' : 'Recompute Payroll' }}
                </button>
            </form>
            @if ($cycle->items->isNotEmpty())
                <form method="POST" action="{{ route('payroll-cycles.submit-hr-review', $cycle) }}">@csrf<button class="btn btn-sm btn-outline-primary"><i class="bi bi-send"></i> Submit for HR Review</button></form>
            @endif
        @elseif ($cycle->status === 'hr_review')
            <form method="POST" action="{{ route('payroll-cycles.approve-hr', $cycle) }}" data-confirm="Approve this cycle on behalf of HR and send it to Finance for review?">@csrf<button class="btn btn-sm btn-success"><i class="bi bi-check2-circle"></i> HR Approve &rarr; Finance</button></form>
            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="collapse" data-bs-target="#rejectForm"><i class="bi bi-x-circle"></i> Reject</button>
        @elseif ($cycle->status === 'finance_review')
            <form method="POST" action="{{ route('payroll-cycles.approve-finance', $cycle) }}" data-confirm="Give final Finance approval for this cycle? A journal voucher can be generated immediately afterwards.">@csrf<button class="btn btn-sm btn-success"><i class="bi bi-check2-circle"></i> Finance Approve</button></form>
            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="collapse" data-bs-target="#rejectForm"><i class="bi bi-x-circle"></i> Reject</button>
        @elseif ($cycle->status === 'approved')
            @unless ($cycle->journalVoucher)
                <form method="POST" action="{{ route('journal-vouchers.build', $cycle) }}">@csrf<button class="btn btn-sm btn-primary"><i class="bi bi-file-earmark-plus"></i> Generate Journal Voucher</button></form>
            @endunless
            <form method="POST" action="{{ route('payroll-cycles.reopen', $cycle) }}" data-confirm="Reopen this cycle for correction? This removes the draft voucher if any.">@csrf<button class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i> Reopen for Correction</button></form>
        @endif
    </div>
</div>

<div class="collapse mb-3" id="rejectForm">
    <div class="card card-body">
        <form method="POST" action="{{ route('payroll-cycles.reject', $cycle) }}" class="row g-2" data-confirm="Send this cycle back to draft? HR/Finance sign-off collected so far will be cleared.">
            @csrf
            <div class="col-md-8"><input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason for rejection" required></div>
            <div class="col-md-4"><button class="btn btn-sm btn-danger"><i class="bi bi-x-circle"></i> Send back to draft</button></div>
        </form>
    </div>
</div>

@include('payroll-cycles._approval-stepper')

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3"><div class="card card-metric shadow-sm"><div class="card-body d-flex align-items-center gap-3">
        <div class="icon-badge icon-badge-teal"><i class="bi bi-people"></i></div>
        <div><div class="text-muted small">Employees</div><div class="value">{{ $cycle->items->count() }}</div></div>
    </div></div></div>
    <div class="col-6 col-md-3"><div class="card card-metric shadow-sm"><div class="card-body d-flex align-items-center gap-3">
        <div class="icon-badge icon-badge-blue"><i class="bi bi-graph-up-arrow"></i></div>
        <div><div class="text-muted small">Gross Earnings</div><div class="value"><x-money :value="$cycle->items->sum('gross_earnings')" :decimals="0" /></div></div>
    </div></div></div>
    <div class="col-6 col-md-3"><div class="card card-metric shadow-sm"><div class="card-body d-flex align-items-center gap-3">
        <div class="icon-badge icon-badge-purple"><i class="bi bi-shield-check"></i></div>
        <div><div class="text-muted small">Deductions</div><div class="value"><x-money :value="$cycle->items->sum('gross_deductions')" :decimals="0" /></div></div>
    </div></div></div>
    <div class="col-6 col-md-3"><div class="card card-metric shadow-sm"><div class="card-body d-flex align-items-center gap-3">
        <div class="icon-badge icon-badge-orange"><i class="bi bi-cash-coin"></i></div>
        <div><div class="text-muted small">Net Payroll</div><div class="value"><x-money :value="$cycle->items->sum('net_pay')" :decimals="0" /></div></div>
    </div></div></div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Payroll Items</span>
        <span class="text-muted small">All amounts in &#8377;</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
            <thead>
                <tr>
                    <th rowspan="2" class="align-middle">Employee</th>
                    <th rowspan="2" class="align-middle text-center">Days<br><span class="text-muted small fw-normal">Paid / Total</span></th>
                    <th colspan="2" class="text-center table-group-earnings">Earnings</th>
                    <th colspan="5" class="text-center table-group-deductions">Statutory Deductions</th>
                    <th rowspan="2" class="align-middle text-end">Reimb.</th>
                    <th rowspan="2" class="align-middle text-end">F&amp;F</th>
                    <th rowspan="2" class="align-middle text-end">Net Pay</th>
                    <th rowspan="2" class="align-middle"></th>
                </tr>
                <tr>
                    <th class="text-end table-group-earnings">Basic</th>
                    <th class="text-end table-group-earnings">Gross</th>
                    <th class="text-end table-group-deductions">PF</th>
                    <th class="text-end table-group-deductions">ESI</th>
                    <th class="text-end table-group-deductions">TDS</th>
                    <th class="text-end table-group-deductions">PT</th>
                    <th class="text-end table-group-deductions">LWF</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($cycle->items as $item)
                <tr>
                    <td class="d-flex align-items-center gap-2"><x-avatar :name="$item->employee->name" size="sm" /> {{ $item->employee->name }}</td>
                    <td class="text-center">
                        {{ $item->payable_days }} / {{ $item->total_days }}
                        @if ($item->manual_lop_days > 0)
                            <br><span class="text-muted small">LOP {{ $item->manual_lop_days }}</span>
                        @endif
                    </td>
                    <td class="text-end table-group-earnings"><x-money :value="$item->basic" :symbol="false" /></td>
                    <td class="text-end table-group-earnings"><x-money :value="$item->gross_earnings" :symbol="false" /></td>
                    <td class="text-end table-group-deductions"><x-money :value="$item->pf_employee" :symbol="false" /></td>
                    <td class="text-end table-group-deductions"><x-money :value="$item->esi_employee" :symbol="false" /></td>
                    <td class="text-end table-group-deductions"><x-money :value="$item->tds" :symbol="false" /></td>
                    <td class="text-end table-group-deductions"><x-money :value="$item->pt" :symbol="false" /></td>
                    <td class="text-end table-group-deductions"><x-money :value="$item->lwf_employee" :symbol="false" /></td>
                    <td class="text-end"><x-money :value="$item->reimbursements_total" :symbol="false" /></td>
                    <td class="text-end">@if($item->is_fnf)<x-money :value="$item->fnf_amount" :symbol="false" />@else — @endif</td>
                    <td class="text-end net-pay-cell"><x-money :value="$item->net_pay" :symbol="false" /></td>
                    <td class="text-nowrap">
                        <a href="{{ route('payslips.show', [$cycle, $item]) }}" target="_blank" class="small"><i class="bi bi-file-earmark-text"></i> Payslip</a>
                        @if ($cycle->status === 'draft')
                            <button type="button" class="btn btn-sm btn-link p-0 ms-2" data-bs-toggle="modal" data-bs-target="#itemEditModal"
                                data-action="{{ route('payroll-cycles.items.update', [$cycle, $item]) }}"
                                data-employee="{{ $item->employee->name }}"
                                data-manual-lop-days="{{ $item->manual_lop_days }}"
                                data-present-days="{{ $item->present_days }}"
                                data-paid-leave-days="{{ $item->paid_leave_days }}"
                                data-weekly-off-days="{{ $item->weekly_off_days }}"
                                data-ot-hours="{{ $item->ot_hours }}"
                                data-ot-amount="{{ $item->ot_amount }}"
                                data-bonus-amount="{{ $item->bonus_amount }}"
                                data-arrears-amount="{{ $item->arrears_amount }}"
                                data-other-earnings-amount="{{ $item->other_earnings_amount }}"
                                data-other-deduction-amount="{{ $item->other_deduction_amount }}"
                                data-pf-wage-override="{{ $item->pf_wage_override }}"
                                data-esi-wage-override="{{ $item->esi_wage_override }}"
                            ><i class="bi bi-pencil-square"></i> Edit</button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="13" class="text-muted p-3">No payroll items yet. Run payroll to compute this cycle.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($cycle->fnfSettlements->isNotEmpty())
<div class="card shadow-sm mb-4">
    <div class="card-header">Full &amp; Final Settlements</div>
    <div class="card-body">
        @foreach ($cycle->fnfSettlements as $fnf)
            <div class="border rounded p-3 mb-2">
                <div class="d-flex justify-content-between">
                    <strong><i class="bi bi-person-dash text-muted"></i> {{ $fnf->employee->name }}</strong>
                    <span class="text-muted small">Exit: {{ $fnf->exit_date->format('d M Y') }} &middot; {{ $fnf->completed_years_of_service }} yrs service &middot; Gratuity eligible: {{ $fnf->gratuity_eligible ? 'Yes' : 'No' }}</span>
                </div>
                @if ($cycle->status === 'draft')
                    <form method="POST" action="{{ route('payroll-cycles.fnf.update', [$cycle, $fnf]) }}" class="row g-2 mt-2">
                        @csrf
                        <div class="col-md-2">
                            <label class="form-label small mb-0">Leave days</label>
                            <input type="number" step="0.5" name="leave_encashment_days" value="{{ $fnf->leave_encashment_days }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-0">Notice days</label>
                            <input type="number" step="0.5" name="notice_pay_days" value="{{ $fnf->notice_pay_days }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-0">Recoveries</label>
                            <input type="number" step="0.01" name="recoveries_amount" value="{{ $fnf->recoveries_amount }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-0">Gratuity override</label>
                            <input type="number" step="0.01" name="gratuity_override" placeholder="auto: {{ number_format($fnf->gratuity_amount, 2) }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-0">Remarks</label>
                            <input type="text" name="remarks" value="{{ $fnf->remarks }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-1 d-flex align-items-end"><button class="btn btn-sm btn-outline-primary">Save</button></div>
                    </form>
                @else
                    <div class="small mt-2">
                        Leave encashment: <x-money :value="$fnf->leave_encashment_amount" /> &middot;
                        Gratuity: <x-money :value="$fnf->gratuity_amount" /> &middot;
                        Notice pay: <x-money :value="$fnf->notice_pay_amount" /> &middot;
                        Recoveries: <x-money :value="$fnf->recoveries_amount" /> &middot;
                        <strong>Net F&amp;F: <x-money :value="$fnf->net_fnf_amount" /></strong>
                    </div>
                @endif
            </div>
        @endforeach
        <p class="text-muted small mb-0">After saving F&amp;F details, click "Recompute Payroll" above to fold the amount into the employee's net pay.</p>
    </div>
</div>
@endif

<div class="row g-3">
    <div class="col-md-7">
        <div class="card shadow-sm mb-3">
            <div class="card-header">Journal Voucher (Financial ERP posting)</div>
            <div class="card-body">
                @if ($cycle->journalVoucher)
                    @php $voucher = $cycle->journalVoucher; @endphp
                    <div class="d-flex justify-content-between mb-2">
                        <div>
                            <strong>{{ $voucher->voucher_number }}</strong>
                            <x-status-badge :status="$voucher->status" class="ms-2" />
                        </div>
                        <div>
                            @if ($voucher->status === 'draft')
                                <form method="POST" action="{{ route('journal-vouchers.post', $voucher) }}" data-confirm="Post this voucher to the Financial ERP? This creates a real accounting entry and cannot be undone from here — only reversed afterwards.">@csrf<button class="btn btn-sm btn-primary"><i class="bi bi-cloud-upload"></i> Post to Financial ERP</button></form>
                            @elseif ($voucher->status === 'posted')
                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="collapse" data-bs-target="#reverseForm"><i class="bi bi-arrow-return-left"></i> Reverse</button>
                            @endif
                        </div>
                    </div>
                    @if ($voucher->external_reference)
                        <div class="small text-muted mb-2">External reference: {{ $voucher->external_reference }} &middot; Posted {{ $voucher->posted_at?->diffForHumans() }}</div>
                    @endif
                    <div class="collapse mb-2" id="reverseForm">
                        <form method="POST" action="{{ route('journal-vouchers.reverse', $voucher) }}" class="row g-2" data-confirm="Reverse this posted voucher? A reversing entry will be posted to the Financial ERP.">
                            @csrf
                            <div class="col-md-8"><input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason for reversal" required></div>
                            <div class="col-md-4"><button class="btn btn-sm btn-danger"><i class="bi bi-check-lg"></i> Confirm Reversal</button></div>
                        </form>
                    </div>
                    <table class="table table-sm">
                        <thead><tr><th>Category</th><th>GL Account</th><th>Cost Centre</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead>
                        <tbody>
                        @foreach ($voucher->lines as $line)
                            <tr>
                                <td>{{ str_replace('_', ' ', $line->category) }}</td>
                                <td>{{ $line->gl_account_code }}</td>
                                <td>{{ $line->cost_centre_code ?? '—' }}</td>
                                <td class="text-end">@if($line->debit > 0)<x-money :value="$line->debit" :symbol="false" />@endif</td>
                                <td class="text-end">@if($line->credit > 0)<x-money :value="$line->credit" :symbol="false" />@endif</td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold"><td colspan="3">Total</td><td class="text-end"><x-money :value="$voucher->totalDebit()" :symbol="false" /></td><td class="text-end"><x-money :value="$voucher->totalCredit()" :symbol="false" /></td></tr>
                        </tfoot>
                    </table>
                @else
                    <p class="text-muted">No journal voucher yet. It is generated once the cycle is approved by Finance.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card shadow-sm mb-3">
            <div class="card-header">Bank Advice</div>
            <div class="card-body">
                @if ($cycle->bankAdviceFile)
                    <div class="small mb-2">{{ $cycle->bankAdviceFile->record_count }} record(s) &middot; Total <x-money :value="$cycle->bankAdviceFile->total_amount" /></div>
                    <a href="{{ route('bank-advice.download', $cycle->bankAdviceFile) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i> Download Excel</a>
                @elseif (in_array($cycle->status, ['approved', 'posted']))
                    <form method="POST" action="{{ route('bank-advice.generate', $cycle) }}">@csrf<button class="btn btn-sm btn-primary"><i class="bi bi-file-earmark-spreadsheet"></i> Generate Bank Advice File</button></form>
                @else
                    <p class="text-muted small mb-0">Available once the cycle is approved.</p>
                @endif
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header">Reconciliation</div>
            <div class="card-body">
                @if ($cycle->reconciliationRecord)
                    @php $rec = $cycle->reconciliationRecord; @endphp
                    <div class="small">Register total: <x-money :value="$rec->payroll_register_total" /></div>
                    <div class="small">Posted to Financial ERP: <x-money :value="$rec->financial_erp_posted_total" /></div>
                    <div class="small mb-2">Variance: <x-status-badge :status="$rec->status" class="fs-6" /> <x-money :value="$rec->variance" /></div>
                @endif
                @if ($cycle->status === 'posted')
                    <form method="POST" action="{{ route('reports.reconciliation.generate', $cycle) }}">@csrf<button class="btn btn-sm btn-outline-primary"><i class="bi bi-clipboard-check"></i> {{ $cycle->reconciliationRecord ? 'Regenerate' : 'Generate' }} Reconciliation</button></form>
                @else
                    <p class="text-muted small mb-0">Available once the voucher has been posted.</p>
                @endif
            </div>
        </div>
    </div>
</div>

@if ($cycle->status === 'draft')
<div class="modal fade" id="itemEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="itemEditForm">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Adjust Payroll Item &mdash; <span id="itemEditEmployeeName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6 class="text-muted small text-uppercase">Attendance</h6>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label small">Loss of Pay Days</label>
                            <input type="number" step="0.5" min="0" name="manual_lop_days" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Present Days</label>
                            <input type="number" step="0.5" min="0" name="present_days" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Paid Leave Days</label>
                            <input type="number" step="0.5" min="0" name="paid_leave_days" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Weekly Off / Holiday Days</label>
                            <input type="number" step="0.5" min="0" name="weekly_off_days" class="form-control form-control-sm">
                        </div>
                    </div>

                    <h6 class="text-muted small text-uppercase">Overtime, Bonus &amp; Other Earnings</h6>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label small">OT Hours</label>
                            <input type="number" step="0.5" min="0" name="ot_hours" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">OT Amount</label>
                            <input type="number" step="0.01" min="0" name="ot_amount" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Bonus</label>
                            <input type="number" step="0.01" min="0" name="bonus_amount" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Arrears</label>
                            <input type="number" step="0.01" min="0" name="arrears_amount" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label small">Other Earnings</label>
                            <input type="number" step="0.01" min="0" name="other_earnings_amount" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Other Deduction</label>
                            <input type="number" step="0.01" min="0" name="other_deduction_amount" class="form-control form-control-sm">
                        </div>
                    </div>

                    <h6 class="text-muted small text-uppercase">Wage Base Overrides</h6>
                    <p class="text-muted small">Leave blank to use the automatically computed wage base (earned basic for PF, gross pay for ESI), ceiling-capped as configured.</p>
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label small">PF Wage Override</label>
                            <input type="number" step="0.01" min="0" name="pf_wage_override" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">ESI Wage Override</label>
                            <input type="number" step="0.01" min="0" name="esi_wage_override" class="form-control form-control-sm">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('itemEditModal').addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        const form = document.getElementById('itemEditForm');
        const data = trigger.dataset;

        form.action = data.action;
        document.getElementById('itemEditEmployeeName').textContent = data.employee;

        const fields = [
            'manualLopDays', 'presentDays', 'paidLeaveDays', 'weeklyOffDays', 'otHours',
            'otAmount', 'bonusAmount', 'arrearsAmount', 'otherEarningsAmount', 'otherDeductionAmount',
            'pfWageOverride', 'esiWageOverride',
        ];

        fields.forEach(function (camelCase) {
            const snakeCase = camelCase.replace(/([A-Z])/g, '_$1').toLowerCase();
            const input = form.querySelector('[name="' + snakeCase + '"]');
            const value = data[camelCase];
            input.value = (value === '' || value === 'null' || value === undefined) ? '' : value;
        });
    });
</script>
@endpush
@endif
@endsection
