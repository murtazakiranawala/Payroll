<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'school_id', 'external_employee_code', 'name', 'designation', 'department',
        'category', 'staff_grade_id', 'date_of_joining', 'date_of_exit', 'employment_status',
        'email', 'phone', 'bank_account_number', 'bank_ifsc', 'bank_name',
        'pan', 'uan_number', 'esi_number', 'photo_path', 'source', 'last_synced_at',
        'pf_applicable', 'esi_applicable', 'pt_applicable', 'lwf_applicable',
    ];

    protected function casts(): array
    {
        return [
            'date_of_joining' => 'date',
            'date_of_exit' => 'date',
            'last_synced_at' => 'datetime',
            // BRD NFR: mask/encrypt sensitive fields (PAN, bank account) at rest.
            'bank_account_number' => 'encrypted',
            'pan' => 'encrypted',
            'pf_applicable' => 'boolean',
            'esi_applicable' => 'boolean',
            'pt_applicable' => 'boolean',
            'lwf_applicable' => 'boolean',
        ];
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function staffGrade()
    {
        return $this->belongsTo(StaffGrade::class);
    }

    public function salaryStructures()
    {
        return $this->hasMany(EmployeeSalaryStructure::class);
    }

    public function activeSalaryStructure(?string $asOfDate = null)
    {
        $asOfDate ??= now()->toDateString();

        return $this->salaryStructures()
            ->where('is_active', true)
            ->where('effective_from', '<=', $asOfDate)
            ->orderByDesc('effective_from')
            ->first();
    }

    /** Basic salary vs. the assigned staff grade's policy band: below_min / within_band / above_max / null. */
    public function salaryComplianceStatus(): ?string
    {
        if (! $this->staffGrade) {
            return null;
        }

        $structure = $this->relationLoaded('salaryStructures')
            ? $this->salaryStructures->firstWhere('is_active', true)
            : $this->activeSalaryStructure();

        return $structure ? $this->staffGrade->complianceFor((float) $structure->basic) : null;
    }

    public function reimbursementClaims()
    {
        return $this->hasMany(ReimbursementClaim::class);
    }

    public function payrollItems()
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function maskedPan(): ?string
    {
        if (! $this->pan) {
            return null;
        }

        return str_repeat('X', max(strlen($this->pan) - 4, 0)).substr($this->pan, -4);
    }

    public function maskedBankAccount(): ?string
    {
        if (! $this->bank_account_number) {
            return null;
        }

        return str_repeat('X', max(strlen($this->bank_account_number) - 4, 0)).substr($this->bank_account_number, -4);
    }
}
