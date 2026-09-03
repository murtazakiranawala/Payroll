<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            $table->decimal('total_days', 5, 2)->default(30);
            $table->decimal('payable_days', 5, 2)->default(30);
            $table->decimal('manual_lop_days', 5, 2)->default(0)
                ->comment('HR-entered loss-of-pay days; no dedicated attendance module in this BRD scope');

            $table->decimal('basic', 12, 2)->default(0);
            $table->decimal('gross_earnings', 12, 2)->default(0);
            $table->decimal('gross_deductions', 12, 2)->default(0);
            $table->decimal('reimbursements_total', 12, 2)->default(0);

            $table->decimal('pf_employee', 12, 2)->default(0);
            $table->decimal('pf_employer', 12, 2)->default(0);
            $table->decimal('esi_employee', 12, 2)->default(0);
            $table->decimal('esi_employer', 12, 2)->default(0);
            $table->decimal('tds', 12, 2)->default(0);
            $table->decimal('pt', 12, 2)->default(0);
            $table->decimal('lwf_employee', 12, 2)->default(0);
            $table->decimal('lwf_employer', 12, 2)->default(0);

            $table->decimal('fnf_amount', 12, 2)->default(0);
            $table->decimal('net_pay', 12, 2)->default(0);

            $table->boolean('is_fnf')->default(false);
            $table->timestamps();

            $table->unique(['payroll_cycle_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};
