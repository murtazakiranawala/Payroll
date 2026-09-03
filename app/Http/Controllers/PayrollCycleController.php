<?php

namespace App\Http\Controllers;

use App\Models\FnfSettlement;
use App\Models\PayrollCycle;
use App\Models\PayrollItem;
use App\Models\School;
use App\Services\FnfSettlementService;
use App\Services\PayrollApprovalService;
use App\Services\PayrollComputationService;
use Illuminate\Http\Request;

class PayrollCycleController extends Controller
{
    public function index(Request $request)
    {
        $cycles = PayrollCycle::with('school')
            ->when($request->filled('school_id'), fn ($q) => $q->where('school_id', $request->integer('school_id')))
            ->orderByDesc('year')->orderByDesc('month')
            ->paginate(20)
            ->withQueryString();

        return view('payroll-cycles.index', ['cycles' => $cycles, 'schools' => School::orderBy('name')->get()]);
    }

    public function create()
    {
        return view('payroll-cycles.create', ['schools' => School::where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'school_id' => ['required', 'exists:schools,id'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
        ]);

        $exists = PayrollCycle::where($data)->first();

        if ($exists) {
            return redirect()->route('payroll-cycles.show', $exists)->with('status', 'A payroll cycle already exists for this school/month.');
        }

        $cycle = PayrollCycle::create($data + ['status' => 'draft', 'created_by' => $request->user()->id]);

        return redirect()->route('payroll-cycles.show', $cycle)->with('status', 'Payroll cycle created. Run computation to generate payroll items.');
    }

    public function show(PayrollCycle $cycle)
    {
        $cycle->load([
            'school', 'creator', 'hrReviewer', 'financeApprover',
            'items.employee', 'fnfSettlements.employee',
            'journalVoucher.lines', 'reconciliationRecord', 'bankAdviceFile',
        ]);

        return view('payroll-cycles.show', ['cycle' => $cycle]);
    }

    public function compute(PayrollCycle $cycle, PayrollComputationService $service)
    {
        try {
            $result = $service->runCycle($cycle);
            $message = "Computed payroll for {$result['processed']} employee(s).";

            if (! empty($result['skipped'])) {
                $message .= ' Skipped (no active salary structure): '.implode(', ', $result['skipped']).'.';
            }

            return back()->with('status', $message);
        } catch (\Throwable $e) {
            return back()->withErrors($e->getMessage());
        }
    }

    public function updateItem(Request $request, PayrollCycle $cycle, PayrollItem $item)
    {
        abort_if($item->payroll_cycle_id !== $cycle->id, 404);
        abort_if($cycle->status !== 'draft', 422, 'The cycle must be in draft status to adjust an item.');

        $data = $request->validate([
            'manual_lop_days' => ['nullable', 'numeric', 'min:0'],
            'present_days' => ['nullable', 'numeric', 'min:0'],
            'paid_leave_days' => ['nullable', 'numeric', 'min:0'],
            'weekly_off_days' => ['nullable', 'numeric', 'min:0'],
            'ot_hours' => ['nullable', 'numeric', 'min:0'],
            'ot_amount' => ['nullable', 'numeric', 'min:0'],
            'bonus_amount' => ['nullable', 'numeric', 'min:0'],
            'arrears_amount' => ['nullable', 'numeric', 'min:0'],
            'other_earnings_amount' => ['nullable', 'numeric', 'min:0'],
            'other_deduction_amount' => ['nullable', 'numeric', 'min:0'],
            'pf_wage_override' => ['nullable', 'numeric', 'min:0'],
            'esi_wage_override' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['manual_lop_days'] = $data['manual_lop_days'] ?? 0;
        $item->update($data);

        return back()->with('status', 'Payroll item updated. Recompute the cycle to apply the changes.');
    }

    public function updateFnf(Request $request, PayrollCycle $cycle, FnfSettlement $fnfSettlement, FnfSettlementService $service)
    {
        abort_if($fnfSettlement->payroll_cycle_id !== $cycle->id, 404);
        abort_if($cycle->status !== 'draft', 422, 'The cycle must be in draft status to update F&F settlement details.');

        $data = $request->validate([
            'leave_encashment_days' => ['nullable', 'numeric', 'min:0'],
            'notice_pay_days' => ['nullable', 'numeric', 'min:0'],
            'recoveries_amount' => ['nullable', 'numeric', 'min:0'],
            'gratuity_override' => ['nullable', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $service->recalculate($fnfSettlement, $data);

        return back()->with('status', 'Full & Final settlement updated.');
    }

    public function submitForHrReview(PayrollCycle $cycle, PayrollApprovalService $service, Request $request)
    {
        return $this->transition(fn () => $service->submitForHrReview($cycle, $request->user()));
    }

    public function approveByHr(PayrollCycle $cycle, PayrollApprovalService $service, Request $request)
    {
        return $this->transition(fn () => $service->approveByHr($cycle, $request->user()));
    }

    public function approveByFinance(PayrollCycle $cycle, PayrollApprovalService $service, Request $request)
    {
        return $this->transition(fn () => $service->approveByFinance($cycle, $request->user()));
    }

    public function reject(Request $request, PayrollCycle $cycle, PayrollApprovalService $service)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        return $this->transition(fn () => $service->reject($cycle, $request->user(), $data['reason']));
    }

    public function reopen(PayrollCycle $cycle, PayrollApprovalService $service, Request $request)
    {
        return $this->transition(fn () => $service->reopenForCorrection($cycle, $request->user()));
    }

    private function transition(\Closure $action)
    {
        try {
            $action();

            return back()->with('status', 'Payroll cycle updated.');
        } catch (\Throwable $e) {
            return back()->withErrors($e->getMessage());
        }
    }
}
