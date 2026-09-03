<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSalaryStructureLine extends Model
{
    protected $fillable = [
        'employee_salary_structure_id', 'salary_component_id', 'amount', 'percentage',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'percentage' => 'decimal:2',
        ];
    }

    public function structure()
    {
        return $this->belongsTo(EmployeeSalaryStructure::class, 'employee_salary_structure_id');
    }

    public function component()
    {
        return $this->belongsTo(SalaryComponent::class, 'salary_component_id');
    }

    /** Resolve this line's amount for a given basic salary (fixed or % of basic). */
    public function resolveAmount(float $basic): float
    {
        if ($this->percentage !== null) {
            return round($basic * ((float) $this->percentage / 100), 2);
        }

        return (float) ($this->amount ?? 0);
    }
}
