<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalVoucherLine extends Model
{
    protected $fillable = [
        'journal_voucher_id', 'category', 'gl_account_code', 'cost_centre_code',
        'debit', 'credit', 'description',
    ];

    protected function casts(): array
    {
        return [
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
        ];
    }

    public function voucher()
    {
        return $this->belongsTo(JournalVoucher::class, 'journal_voucher_id');
    }
}
