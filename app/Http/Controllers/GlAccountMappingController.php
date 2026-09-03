<?php

namespace App\Http\Controllers;

use App\Models\GlAccountMapping;
use App\Models\School;
use Illuminate\Http\Request;

class GlAccountMappingController extends Controller
{
    public function index()
    {
        return view('gl-mappings.index', [
            'mappings' => GlAccountMapping::with('school')->orderBy('category')->get(),
            'schools' => School::orderBy('name')->get(),
            'categories' => GlAccountMapping::CATEGORIES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'school_id' => ['nullable', 'exists:schools,id'],
            'category' => ['required', 'in:'.implode(',', GlAccountMapping::CATEGORIES)],
            'gl_account_code' => ['required', 'string', 'max:64'],
            'cost_centre_code' => ['nullable', 'string', 'max:64'],
        ]);

        GlAccountMapping::updateOrCreate(
            ['school_id' => $data['school_id'] ?? null, 'category' => $data['category']],
            ['gl_account_code' => $data['gl_account_code'], 'cost_centre_code' => $data['cost_centre_code'] ?? null]
        );

        return redirect()->route('gl-mappings.index')->with('status', 'GL mapping saved.');
    }

    public function destroy(GlAccountMapping $glAccountMapping)
    {
        $glAccountMapping->delete();

        return redirect()->route('gl-mappings.index')->with('status', 'GL mapping removed.');
    }
}
