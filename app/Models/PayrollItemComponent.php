<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollItemComponent extends Model
{
    protected $fillable = ['payroll_item_id', 'salary_component_id', 'type', 'amount'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function payrollItem()
    {
        return $this->belongsTo(PayrollItem::class);
    }

    public function component()
    {
        return $this->belongsTo(SalaryComponent::class, 'salary_component_id');
    }
}
