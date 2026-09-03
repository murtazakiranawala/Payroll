<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_items', function (Blueprint $table) {
            // Attendance detail - informational only, doesn't drive any
            // earned-salary formula (only payable_days does).
            $table->decimal('present_days', 5, 2)->nullable()->after('manual_lop_days');
            $table->decimal('paid_leave_days', 5, 2)->nullable()->after('present_days');
            $table->decimal('weekly_off_days', 5, 2)->nullable()->after('paid_leave_days');
            $table->decimal('ot_hours', 5, 2)->nullable()->after('weekly_off_days');

            // Manual earnings/deductions HR enters per cycle (never derived
            // from a generic formula - see Salary Register "Instructions").
            $table->decimal('ot_amount', 12, 2)->default(0)->after('gross_earnings');
            $table->decimal('bonus_amount', 12, 2)->default(0)->after('ot_amount');
            $table->decimal('arrears_amount', 12, 2)->default(0)->after('bonus_amount');
            $table->decimal('other_earnings_amount', 12, 2)->default(0)->after('arrears_amount');
            $table->decimal('other_deduction_amount', 12, 2)->default(0)->after('gross_deductions');

            // Wage-base overrides + the resolved wage base actually used,
            // for the Salary Register's PF/ESI Wages columns.
            $table->decimal('pf_wage_override', 12, 2)->nullable()->after('lwf_employer');
            $table->decimal('esi_wage_override', 12, 2)->nullable()->after('pf_wage_override');
            $table->decimal('pf_wages', 12, 2)->default(0)->after('esi_wage_override');
            $table->decimal('esi_wages', 12, 2)->default(0)->after('pf_wages');
            $table->decimal('pf_employer_edli', 12, 2)->default(0)->after('esi_wages');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->dropColumn([
                'present_days', 'paid_leave_days', 'weekly_off_days', 'ot_hours',
                'ot_amount', 'bonus_amount', 'arrears_amount', 'other_earnings_amount', 'other_deduction_amount',
                'pf_wage_override', 'esi_wage_override', 'pf_wages', 'esi_wages', 'pf_employer_edli',
            ]);
        });
    }
};
