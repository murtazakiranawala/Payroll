<?php

namespace App\Http\Controllers;

use App\Models\StaffGrade;
use Illuminate\Http\Request;

class StaffGradeController extends Controller
{
    public function index()
    {
        return view('staff-grades.index', [
            'grades' => StaffGrade::orderBy('staff_type')->orderBy('sort_order')->get(),
        ]);
    }

    public function create()
    {
        return view('staff-grades.form', ['grade' => new StaffGrade()]);
    }

    public function store(Request $request)
    {
        StaffGrade::create($this->validated($request));

        return redirect()->route('staff-grades.index')->with('status', 'Staff grade created.');
    }

    public function edit(StaffGrade $staffGrade)
    {
        return view('staff-grades.form', ['grade' => $staffGrade]);
    }

    public function update(Request $request, StaffGrade $staffGrade)
    {
        $staffGrade->update($this->validated($request));

        return redirect()->route('staff-grades.index')->with('status', 'Staff grade updated.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:32', 'unique:staff_grades,code,'.$request->route('staffGrade')?->id],
            'description' => ['required', 'string', 'max:255'],
            'applicable_to' => ['nullable', 'string', 'max:255'],
            'staff_type' => ['required', 'in:teaching,administrative,management'],
            'min_basic' => ['nullable', 'numeric', 'min:0'],
            'max_basic' => ['nullable', 'numeric', 'gte:min_basic'],
            'yearly_increment' => ['nullable', 'numeric', 'min:0'],
        ]);
    }
}
