<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\StatutoryRateConfig;
use Illuminate\Http\Request;

class StatutoryRateConfigController extends Controller
{
    public function index()
    {
        return view('statutory-rates.index', [
            'configs' => StatutoryRateConfig::with('school')->orderBy('type')->orderByDesc('effective_from')->get(),
        ]);
    }

    public function create()
    {
        return view('statutory-rates.form', [
            'config' => new StatutoryRateConfig(),
            'schools' => School::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        StatutoryRateConfig::create($this->validated($request));

        return redirect()->route('statutory-rates.index')->with('status', 'Statutory rate configuration created.');
    }

    public function edit(StatutoryRateConfig $statutoryRateConfig)
    {
        return view('statutory-rates.form', [
            'config' => $statutoryRateConfig,
            'schools' => School::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, StatutoryRateConfig $statutoryRateConfig)
    {
        $statutoryRateConfig->update($this->validated($request));

        return redirect()->route('statutory-rates.index')->with('status', 'Statutory rate configuration updated.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'school_id' => ['nullable', 'exists:schools,id'],
            'type' => ['required', 'in:PF,ESI,TDS,PT,LWF'],
            'name' => ['required', 'string', 'max:255'],
            'config_json' => ['required', 'string'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $decoded = json_decode($data['config_json'], true);

        if (! is_array($decoded)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'config_json' => 'The rate/slab configuration must be valid JSON. See the placeholder examples below.',
            ]);
        }

        return [
            'school_id' => $data['school_id'] ?? null,
            'type' => $data['type'],
            'name' => $data['name'],
            'config' => $decoded,
            'effective_from' => $data['effective_from'],
            'effective_to' => $data['effective_to'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ];
    }
}
