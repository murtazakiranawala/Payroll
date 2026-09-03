<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\School;
use App\Models\StaffGrade;
use App\Services\EmployeeSyncService;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $employees = Employee::with(['school', 'staffGrade', 'salaryStructures' => fn ($q) => $q->where('is_active', true)])
            ->when($request->filled('school_id'), fn ($q) => $q->where('school_id', $request->integer('school_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('employment_status', $request->string('status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->string('q');
                $q->where(fn ($qq) => $qq->where('name', 'like', "%{$term}%")->orWhere('external_employee_code', 'like', "%{$term}%"));
            })
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('employees.index', [
            'employees' => $employees,
            'schools' => School::orderBy('name')->get(),
        ]);
    }

    public function show(Employee $employee)
    {
        $employee->load(['school', 'staffGrade', 'salaryStructures.lines.component', 'reimbursementClaims', 'payrollItems.payrollCycle']);

        return view('employees.show', ['employee' => $employee]);
    }

    public function create()
    {
        return view('employees.form', [
            'employee' => new Employee(),
            'schools' => School::orderBy('name')->get(),
            'staffGrades' => StaffGrade::orderBy('staff_type')->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['source'] = 'manual';

        $employee = Employee::create($data);

        return redirect()->route('employees.show', $employee)->with('status', 'Employee created.');
    }

    public function edit(Employee $employee)
    {
        return view('employees.form', [
            'employee' => $employee,
            'schools' => School::orderBy('name')->get(),
            'staffGrades' => StaffGrade::orderBy('staff_type')->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $this->validated($request, $employee);

        // PAN / bank account are encrypted and never redisplayed in the edit
        // form; leaving them blank means "no change", not "clear the value".
        foreach (['pan', 'bank_account_number'] as $sensitive) {
            if (empty($data[$sensitive])) {
                unset($data[$sensitive]);
            }
        }

        $employee->update($data);

        return redirect()->route('employees.show', $employee)->with('status', 'Employee updated.');
    }

    /** BRD FR-1.6: manual re-sync trigger. */
    public function syncNow(Request $request, EmployeeSyncService $service)
    {
        $data = $request->validate([
            'school_id' => ['required', 'exists:schools,id'],
            'full' => ['nullable', 'boolean'],
        ]);

        $school = School::findOrFail($data['school_id']);
        $log = $service->syncSchool($school, (bool) ($data['full'] ?? false), 'manual', $request->user()->id);

        return redirect()->route('employees.index', ['school_id' => $school->id])
            ->with('status', "Sync {$log->status}: fetched {$log->records_fetched}, created {$log->records_created}, updated {$log->records_updated}, failed {$log->records_failed}.");
    }

    private function validated(Request $request, ?Employee $employee = null): array
    {
        $data = $request->validate([
            'school_id' => ['required', 'exists:schools,id'],
            'external_employee_code' => ['nullable', 'string', 'max:64'],
            'name' => ['required', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'category' => ['required', 'in:teaching,non_teaching,administrative,support,other'],
            'staff_grade_id' => ['nullable', 'exists:staff_grades,id'],
            'date_of_joining' => ['nullable', 'date'],
            'date_of_exit' => ['nullable', 'date'],
            'employment_status' => ['required', 'in:active,on_leave,exited'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'bank_account_number' => ['nullable', 'string', 'max:64'],
            'bank_ifsc' => ['nullable', 'string', 'max:16'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'pan' => ['nullable', 'string', 'max:16'],
            'uan_number' => ['nullable', 'string', 'max:32'],
            'esi_number' => ['nullable', 'string', 'max:32'],
        ]);

        // An unchecked checkbox is simply absent from the submitted form, so
        // the fallback must be false (native HTML semantics) - the form
        // itself is what renders these checked by default for new employees.
        foreach (['pf_applicable', 'esi_applicable', 'pt_applicable', 'lwf_applicable'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        return $data;
    }
}
