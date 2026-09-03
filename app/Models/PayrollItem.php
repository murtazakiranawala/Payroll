<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollItem extends Model
{
    protected $fillable = [
        'payroll_cycle_id', 'employee_id', 'total_days', 'payable_days', 'manual_lop_days',
        'present_days', 'paid_leave_days', 'weekly_off_days', 'ot_hours',
        'basic', 'gross_earnings', 'ot_amount', 'bonus_amount', 'arrears_amount', 'other_earnings_amount',
        'gross_deductions', 'other_deduction_amount', 'reimbursements_total',
        'pf_employee', 'pf_employer', 'esi_employee', 'esi_employer', 'tds', 'pt',
        'lwf_employee', 'lwf_employer', 'fnf_amount', 'net_pay', 'is_fnf',
        'pf_wage_override', 'esi_wage_override', 'pf_wages', 'esi_wages', 'pf_employer_edli',
    ];

    protected function casts(): array
    {
        return [
            'is_fnf' => 'boolean',
            'total_days' => 'decimal:2',
            'payable_days' => 'decimal:2',
            'manual_lop_days' => 'decimal:2',
            'present_days' => 'decimal:2',
            'paid_leave_days' => 'decimal:2',
            'weekly_off_days' => 'decimal:2',
            'ot_hours' => 'decimal:2',
            'basic' => 'decimal:2',
            'gross_earnings' => 'decimal:2',
            'ot_amount' => 'decimal:2',
            'bonus_amount' => 'decimal:2',
            'arrears_amount' => 'decimal:2',
            'other_earnings_amount' => 'decimal:2',
            'gross_deductions' => 'decimal:2',
            'other_deduction_amount' => 'decimal:2',
            'reimbursements_total' => 'decimal:2',
            'pf_employee' => 'decimal:2',
            'pf_employer' => 'decimal:2',
            'esi_employee' => 'decimal:2',
            'esi_employer' => 'decimal:2',
            'tds' => 'decimal:2',
            'pt' => 'decimal:2',
            'lwf_employee' => 'decimal:2',
            'lwf_employer' => 'decimal:2',
            'fnf_amount' => 'decimal:2',
            'net_pay' => 'decimal:2',
            'pf_wage_override' => 'decimal:2',
            'esi_wage_override' => 'decimal:2',
            'pf_wages' => 'decimal:2',
            'esi_wages' => 'decimal:2',
            'pf_employer_edli' => 'decimal:2',
        ];
    }

    public function payrollCycle()
    {
        return $this->belongsTo(PayrollCycle::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function components()
    {
        return $this->hasMany(PayrollItemComponent::class);
    }

    public function fnfSettlement()
    {
        return $this->hasOne(FnfSettlement::class);
    }

    public function totalStatutoryDeductions(): float
    {
        return (float) $this->pf_employee + (float) $this->esi_employee
            + (float) $this->tds + (float) $this->pt + (float) $this->lwf_employee;
    }
}
