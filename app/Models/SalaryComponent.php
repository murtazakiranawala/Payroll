<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryComponent extends Model
{
    protected $fillable = [
        'code', 'name', 'type', 'calculation_type', 'default_percentage',
        'is_statutory', 'statutory_type', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_statutory' => 'boolean',
            'is_active' => 'boolean',
            'default_percentage' => 'decimal:2',
        ];
    }

    public function isEarning(): bool
    {
        return $this->type === 'earning';
    }

    public function isDeduction(): bool
    {
        return $this->type === 'deduction';
    }
}
