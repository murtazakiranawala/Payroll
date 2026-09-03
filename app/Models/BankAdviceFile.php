<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankAdviceFile extends Model
{
    protected $fillable = [
        'payroll_cycle_id', 'file_path', 'total_amount', 'record_count', 'generated_by', 'generated_at',
    ];

    protected function casts(): array
    {
        return ['generated_at' => 'datetime'];
    }

    public function payrollCycle()
    {
        return $this->belongsTo(PayrollCycle::class);
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
