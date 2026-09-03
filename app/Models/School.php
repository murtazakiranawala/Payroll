<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    protected $fillable = [
        'name', 'code', 'aiims_school_code', 'gl_cost_centre_code',
        'address', 'location_tier', 'contact_email', 'contact_phone', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public const TIER_LABELS = [
        'tier_1' => 'Tier 1',
        'tier_2' => 'Tier 2',
        'tier_3' => 'Tier 3',
    ];

    /** Compensation Policy Annexure B-2: HRA as % of basic, by location tier. */
    public function hraPercent(): ?float
    {
        return match ($this->location_tier) {
            'tier_1' => 40.0,
            'tier_2' => 30.0,
            'tier_3' => 20.0,
            default => null,
        };
    }

    /** Compensation Policy Annexure B-2: CCA as % of basic, by location tier. */
    public function ccaPercent(): ?float
    {
        return match ($this->location_tier) {
            'tier_1' => 60.0,
            'tier_2' => 40.0,
            'tier_3' => 20.0,
            default => null,
        };
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function payrollCycles()
    {
        return $this->hasMany(PayrollCycle::class);
    }
}
