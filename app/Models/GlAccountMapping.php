<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlAccountMapping extends Model
{
    public const CATEGORIES = [
        'salary_expense',
        'pf_employer_expense',
        'esi_employer_expense',
        'lwf_employer_expense',
        'reimbursement_expense',
        'fnf_expense',
        'pf_liability',
        'esi_liability',
        'tds_payable',
        'pt_payable',
        'lwf_liability',
        'other_deductions_payable',
        'net_pay_payable',
    ];

    protected $fillable = ['school_id', 'category', 'gl_account_code', 'cost_centre_code'];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /** School-specific mapping wins; falls back to the global (school_id null) default. */
    public static function resolve(?int $schoolId, string $category): ?self
    {
        return static::where('category', $category)
            ->where(function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId)->orWhereNull('school_id');
            })
            ->orderByRaw('school_id IS NULL')
            ->first();
    }
}
