<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A staff grade from the Staff Grading & Compensation Policy (Annexures A-1,
 * A-2, B-1) - defines the monthly basic salary band and standard yearly
 * increment for employees assigned to it.
 */
class StaffGrade extends Model
{
    protected $fillable = [
        'code', 'description', 'applicable_to', 'staff_type',
        'min_basic', 'max_basic', 'yearly_increment', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'min_basic' => 'decimal:2',
            'max_basic' => 'decimal:2',
            'yearly_increment' => 'decimal:2',
        ];
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    /** below_min / within_band / above_max, or null if the band isn't set (e.g. a TBA grade). */
    public function complianceFor(?float $basic): ?string
    {
        if ($basic === null || $this->min_basic === null || $this->max_basic === null) {
            return null;
        }

        return match (true) {
            $basic < (float) $this->min_basic => 'below_min',
            $basic > (float) $this->max_basic => 'above_max',
            default => 'within_band',
        };
    }

    /** Policy §6: a new recruit's joining basic can exceed the grade minimum by up to 4 standard increments. */
    public function maxJoiningBasic(): ?float
    {
        if ($this->min_basic === null || $this->yearly_increment === null) {
            return null;
        }

        return (float) $this->min_basic + 4 * (float) $this->yearly_increment;
    }

    /** Policy §7: the largest sanctioned annual increment is 2x the standard increment ("above average" performance). */
    public function maxAnnualIncrement(): ?float
    {
        return $this->yearly_increment === null ? null : 2 * (float) $this->yearly_increment;
    }

    /**
     * Policy §7's increment quantum by performance rating: below average = 0,
     * average = the standard increment, above average = 2x the standard
     * increment. Null if this grade has no increment amount set (TBA).
     */
    public function incrementFor(string $rating): ?float
    {
        if ($this->yearly_increment === null) {
            return null;
        }

        return match ($rating) {
            'below_average' => 0.0,
            'average' => (float) $this->yearly_increment,
            'above_average' => 2 * (float) $this->yearly_increment,
            default => null,
        };
    }
}
