<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Staff Grading & Compensation Policy §7: annual increments are a quantum of
 * the employee's grade's standard increment, driven purely by a performance
 * rating - below average (0x), average (1x), or above average (2x). This
 * controller computes that amount instead of HR working it out by hand and
 * typing a raw basic into the general salary-structure form.
 */
class IncrementController extends Controller
{
    public function create(Employee $employee)
    {
        $this->ensureEligible($employee);

        return view('increments.create', [
            'employee' => $employee->load('staffGrade'),
            'previousStructure' => $employee->activeSalaryStructure(),
        ]);
    }

    public function store(Request $request, Employee $employee)
    {
        $this->ensureEligible($employee);

        $data = $request->validate([
            'performance_rating' => ['required', 'in:below_average,average,above_average'],
            'effective_from' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->applyIncrement($employee, $data['performance_rating'], $data['effective_from'], $data['remarks'] ?? null, $request->user()->id);

        return redirect()->route('employees.show', $employee)->with('status', 'Increment applied.');
    }

    public function bulkForm(Request $request)
    {
        $employees = Employee::with(['school', 'staffGrade', 'salaryStructures' => fn ($q) => $q->where('is_active', true)])
            ->whereNotNull('staff_grade_id')
            ->where('employment_status', 'active')
            ->when($request->filled('school_id'), fn ($q) => $q->where('school_id', $request->integer('school_id')))
            ->orderBy('name')
            ->get()
            ->filter(fn ($e) => $e->activeSalaryStructure() && $e->staffGrade->yearly_increment !== null)
            ->values();

        return view('increments.bulk', [
            'employees' => $employees,
            'schools' => School::orderBy('name')->get(),
        ]);
    }

    public function bulkStore(Request $request)
    {
        $data = $request->validate([
            'effective_from' => ['required', 'date'],
            'ratings' => ['required', 'array'],
            'ratings.*' => ['nullable', 'in:below_average,average,above_average'],
        ]);

        $applied = 0;

        DB::transaction(function () use ($data, $request, &$applied) {
            foreach ($data['ratings'] as $employeeId => $rating) {
                if (! $rating) {
                    continue;
                }

                $employee = Employee::with('staffGrade')->find($employeeId);

                if (! $employee || ! $employee->staffGrade || $employee->staffGrade->yearly_increment === null) {
                    continue;
                }

                $this->applyIncrement($employee, $rating, $data['effective_from'], null, $request->user()->id);
                $applied++;
            }
        });

        return redirect()->route('increments.bulk')->with('status', "Increment applied to {$applied} employee(s).");
    }

    private function applyIncrement(Employee $employee, string $rating, string $effectiveFrom, ?string $remarks, int $userId): void
    {
        $previous = $employee->activeSalaryStructure();
        $grade = $employee->staffGrade;
        $incrementAmount = $grade->incrementFor($rating);
        $newBasic = (float) $previous->basic + $incrementAmount;

        $employee->salaryStructures()->where('is_active', true)->update(['is_active' => false]);

        $structure = $employee->salaryStructures()->create([
            'effective_from' => $effectiveFrom,
            'ctc' => $previous->ctc,
            'basic' => $newBasic,
            'is_active' => true,
            'performance_rating' => $rating,
            'remarks' => $remarks,
            'created_by' => $userId,
        ]);

        // Carry forward the same allowance lines (percentages auto-scale with
        // the new basic; fixed amounts stay as they were) - an increment
        // changes basic, not the rest of the structure.
        foreach ($previous->lines as $line) {
            $structure->lines()->create([
                'salary_component_id' => $line->salary_component_id,
                'amount' => $line->amount,
                'percentage' => $line->percentage,
            ]);
        }
    }

    private function ensureEligible(Employee $employee): void
    {
        $employee->loadMissing('staffGrade');

        if (! $employee->staffGrade) {
            throw ValidationException::withMessages(['employee' => 'Assign a staff grade to this employee before giving an increment.'])
                ->redirectTo(route('employees.edit', $employee));
        }

        if ($employee->staffGrade->yearly_increment === null) {
            throw ValidationException::withMessages(['employee' => "Grade {$employee->staffGrade->code}'s pay band isn't finalized yet (TBA), so no standard increment amount exists."])
                ->redirectTo(route('employees.show', $employee));
        }

        if (! $employee->activeSalaryStructure()) {
            throw ValidationException::withMessages(['employee' => 'This employee has no salary structure yet - set up their first one before giving an increment.'])
                ->redirectTo(route('salary-structures.create', $employee));
        }
    }
}
