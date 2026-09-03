<?php

namespace App\Models;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class PayrollCycle extends Model
{
    use Auditable;

    protected $fillable = [
        'school_id', 'month', 'year', 'status', 'computed_at', 'created_by',
        'hr_reviewed_by', 'hr_reviewed_at', 'finance_approved_by', 'finance_approved_at',
        'rejected_reason', 'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'computed_at' => 'datetime',
            'hr_reviewed_at' => 'datetime',
            'finance_approved_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function items()
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function fnfSettlements()
    {
        return $this->hasMany(FnfSettlement::class);
    }

    public function journalVoucher()
    {
        return $this->hasOne(JournalVoucher::class);
    }

    public function reconciliationRecord()
    {
        return $this->hasOne(ReconciliationRecord::class);
    }

    public function bankAdviceFile()
    {
        return $this->hasOne(BankAdviceFile::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function hrReviewer()
    {
        return $this->belongsTo(User::class, 'hr_reviewed_by');
    }

    public function financeApprover()
    {
        return $this->belongsTo(User::class, 'finance_approved_by');
    }

    public function label(): string
    {
        return \Carbon\Carbon::create($this->year, $this->month, 1)->format('F Y');
    }

    public function periodStart(): \Carbon\Carbon
    {
        return \Carbon\Carbon::create($this->year, $this->month, 1)->startOfDay();
    }

    public function periodEnd(): \Carbon\Carbon
    {
        return $this->periodStart()->copy()->endOfMonth();
    }
}
