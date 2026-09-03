<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSalaryStructure extends Model
{
    protected $fillable = [
        'employee_id', 'effective_from', 'ctc', 'basic', 'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'ctc' => 'decimal:2',
            'basic' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function lines()
    {
        return $this->hasMany(EmployeeSalaryStructureLine::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
