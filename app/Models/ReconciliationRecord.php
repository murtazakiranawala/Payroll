<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReconciliationRecord extends Model
{
    protected $fillable = [
        'payroll_cycle_id', 'payroll_register_total', 'financial_erp_posted_total',
        'variance', 'status', 'generated_at',
    ];

    protected function casts(): array
    {
        return ['generated_at' => 'datetime'];
    }

    public function payrollCycle()
    {
        return $this->belongsTo(PayrollCycle::class);
    }
}
