<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\ReimbursementClaim;
use Illuminate\Http\Request;

class ReimbursementClaimController extends Controller
{
    public function index(Request $request)
    {
        $claims = ReimbursementClaim::with('employee.school')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('claim_date')
            ->paginate(25)
            ->withQueryString();

        return view('reimbursements.index', ['claims' => $claims]);
    }

    public function create()
    {
        return view('reimbursements.create', ['employees' => Employee::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'category' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'claim_date' => ['required', 'date'],
        ]);

        ReimbursementClaim::create($data + ['status' => 'pending']);

        return redirect()->route('reimbursements.index')->with('status', 'Reimbursement claim submitted.');
    }

    public function approve(Request $request, ReimbursementClaim $reimbursementClaim)
    {
        abort_if($reimbursementClaim->status !== 'pending', 422, 'This claim has already been processed.');

        $reimbursementClaim->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('status', 'Claim approved.');
    }

    public function reject(Request $request, ReimbursementClaim $reimbursementClaim)
    {
        abort_if($reimbursementClaim->status !== 'pending', 422, 'This claim has already been processed.');

        $reimbursementClaim->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('status', 'Claim rejected.');
    }
}
