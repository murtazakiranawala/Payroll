<?php

namespace Database\Seeders;

use App\Models\GlAccountMapping;
use Illuminate\Database\Seeder;

class GlAccountMappingSeeder extends Seeder
{
    public function run(): void
    {
        $mappings = [
            'salary_expense' => '5001-SALARY-EXP',
            'pf_employer_expense' => '5002-PF-EMPLOYER-EXP',
            'esi_employer_expense' => '5003-ESI-EMPLOYER-EXP',
            'lwf_employer_expense' => '5004-LWF-EMPLOYER-EXP',
            'reimbursement_expense' => '5005-REIMBURSEMENT-EXP',
            'fnf_expense' => '5006-FNF-EXP',
            'pf_liability' => '2001-PF-PAYABLE',
            'esi_liability' => '2002-ESI-PAYABLE',
            'tds_payable' => '2003-TDS-PAYABLE',
            'pt_payable' => '2004-PT-PAYABLE',
            'lwf_liability' => '2005-LWF-PAYABLE',
            'other_deductions_payable' => '2007-OTHER-RECOVERIES-PAYABLE',
            'net_pay_payable' => '2006-SALARY-PAYABLE',
        ];

        foreach ($mappings as $category => $glCode) {
            GlAccountMapping::updateOrCreate(
                ['school_id' => null, 'category' => $category],
                ['gl_account_code' => $glCode]
            );
        }
    }
}
