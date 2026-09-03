<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FnfSettlement extends Model
{
    protected $fillable = [
        'employee_id', 'payroll_cycle_id', 'payroll_item_id', 'exit_date',
        'completed_years_of_service', 'leave_encashment_days', 'leave_encashment_amount',
        'gratuity_eligible', 'gratuity_amount', 'notice_pay_days', 'notice_pay_amount',
        'recoveries_amount', 'net_fnf_amount', 'remarks', 'finalized',
    ];

    protected function casts(): array
    {
        return [
            'exit_date' => 'date',
            'gratuity_eligible' => 'boolean',
            'finalized' => 'boolean',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function payrollCycle()
    {
        return $this->belongsTo(PayrollCycle::class);
    }

    public function payrollItem()
    {
        return $this->belongsTo(PayrollItem::class);
    }
}
