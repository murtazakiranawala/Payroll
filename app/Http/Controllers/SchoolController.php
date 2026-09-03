<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    public function index()
    {
        return view('schools.index', ['schools' => School::withCount('employees')->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('schools.form', ['school' => new School()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        School::create($data);

        return redirect()->route('schools.index')->with('status', 'School created.');
    }

    public function edit(School $school)
    {
        return view('schools.form', ['school' => $school]);
    }

    public function update(Request $request, School $school)
    {
        $data = $this->validated($request, $school);
        $school->update($data);

        return redirect()->route('schools.index')->with('status', 'School updated.');
    }

    private function validated(Request $request, ?School $school = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:32', 'unique:schools,code'.($school ? ",{$school->id}" : '')],
            'aiims_school_code' => ['nullable', 'string', 'max:64'],
            'gl_cost_centre_code' => ['nullable', 'string', 'max:64'],
            'address' => ['nullable', 'string', 'max:255'],
            'location_tier' => ['nullable', 'in:tier_1,tier_2,tier_3'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:32'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        return $validated;
    }
}
