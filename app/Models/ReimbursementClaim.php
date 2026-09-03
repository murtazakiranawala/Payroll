<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReimbursementClaim extends Model
{
    protected $fillable = [
        'employee_id', 'category', 'description', 'amount', 'claim_date',
        'status', 'approved_by', 'approved_at', 'payroll_cycle_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'claim_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payrollCycle()
    {
        return $this->belongsTo(PayrollCycle::class, 'payroll_cycle_id');
    }
}
