<?php

namespace App\Models;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class JournalVoucher extends Model
{
    use Auditable;

    protected $fillable = [
        'payroll_cycle_id', 'voucher_number', 'status', 'idempotency_key',
        'external_reference', 'posted_at', 'reversed_at', 'reversal_of_voucher_id',
    ];

    protected function casts(): array
    {
        return [
            'posted_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    public function payrollCycle()
    {
        return $this->belongsTo(PayrollCycle::class);
    }

    public function lines()
    {
        return $this->hasMany(JournalVoucherLine::class);
    }

    public function reversalOf()
    {
        return $this->belongsTo(self::class, 'reversal_of_voucher_id');
    }

    public function totalDebit(): float
    {
        return (float) $this->lines->sum('debit');
    }

    public function totalCredit(): float
    {
        return (float) $this->lines->sum('credit');
    }
}
