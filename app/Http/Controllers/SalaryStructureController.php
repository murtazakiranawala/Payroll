<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\SalaryComponent;
use Illuminate\Http\Request;

class SalaryStructureController extends Controller
{
    public function index(Request $request)
    {
        $employees = Employee::with(['school', 'staffGrade', 'salaryStructures' => fn ($q) => $q->where('is_active', true)->latest('effective_from')])
            ->when($request->filled('school_id'), fn ($q) => $q->where('school_id', $request->integer('school_id')))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('salary-structures.index', [
            'employees' => $employees,
            'schools' => \App\Models\School::orderBy('name')->get(),
        ]);
    }

    public function create(Employee $employee)
    {
        return view('salary-structures.create', [
            'employee' => $employee,
            'components' => SalaryComponent::where('is_active', true)->where('is_statutory', false)->orderBy('sort_order')->get(),
            'previousStructure' => $employee->activeSalaryStructure(),
        ]);
    }

    public function store(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'effective_from' => ['required', 'date'],
            'ctc' => ['nullable', 'numeric', 'min:0'],
            'basic' => ['required', 'numeric', 'min:0'],
            'lines' => ['array'],
            'lines.*.salary_component_id' => ['required_with:lines', 'exists:salary_components,id'],
            'lines.*.amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $previousStructure = $employee->activeSalaryStructure();
        $warning = $this->policyWarning($employee, $previousStructure, (float) $data['basic']);

        $employee->salaryStructures()->where('is_active', true)->update(['is_active' => false]);

        $structure = $employee->salaryStructures()->create([
            'effective_from' => $data['effective_from'],
            'ctc' => $data['ctc'] ?? null,
            'basic' => $data['basic'],
            'is_active' => true,
            'created_by' => $request->user()->id,
        ]);

        foreach ($data['lines'] ?? [] as $line) {
            if (empty($line['amount']) && empty($line['percentage'])) {
                continue;
            }

            $structure->lines()->create([
                'salary_component_id' => $line['salary_component_id'],
                'amount' => $line['amount'] ?? null,
                'percentage' => $line['percentage'] ?? null,
            ]);
        }

        $redirect = redirect()->route('employees.show', $employee)->with('status', 'Salary structure saved.');

        return $warning ? $redirect->with('policy_warning', $warning) : $redirect;
    }

    /**
     * Staff Grading & Compensation Policy checks - advisory only (the policy
     * itself allows justified exceptions, subject to reporting), so this
     * flags a warning rather than blocking the save. §6: a new recruit's
     * joining basic may exceed the grade minimum by at most 4 standard
     * increments. §7: an existing employee's annual increment may not exceed
     * 2x the grade's standard increment (the "above average performance" cap).
     */
    private function policyWarning(Employee $employee, $previousStructure, float $newBasic): ?string
    {
        $grade = $employee->staffGrade;

        if (! $grade) {
            return null;
        }

        if (! $previousStructure) {
            $maxJoining = $grade->maxJoiningBasic();

            if ($maxJoining !== null && $newBasic > $maxJoining) {
                return "Joining basic of ₹".number_format($newBasic)." exceeds the policy cap for grade {$grade->code} "
                    ."(minimum + 4 increments = ₹".number_format($maxJoining).").";
            }

            return null;
        }

        $increment = $newBasic - (float) $previousStructure->basic;
        $maxIncrement = $grade->maxAnnualIncrement();

        if ($increment > 0 && $maxIncrement !== null && $increment > $maxIncrement) {
            return "Increment of ₹".number_format($increment)." exceeds the policy maximum for grade {$grade->code} "
                ."(2× standard increment = ₹".number_format($maxIncrement).").";
        }

        return null;
    }
}
