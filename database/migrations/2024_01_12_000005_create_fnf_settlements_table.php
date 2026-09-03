<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fnf_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_item_id')->nullable()->constrained()->nullOnDelete();
            $table->date('exit_date');
            $table->decimal('completed_years_of_service', 5, 2)->default(0);
            $table->decimal('leave_encashment_days', 5, 2)->default(0);
            $table->decimal('leave_encashment_amount', 12, 2)->default(0);
            $table->boolean('gratuity_eligible')->default(false);
            $table->decimal('gratuity_amount', 12, 2)->default(0);
            $table->decimal('notice_pay_days', 5, 2)->default(0);
            $table->decimal('notice_pay_amount', 12, 2)->default(0);
            $table->decimal('recoveries_amount', 12, 2)->default(0);
            $table->decimal('net_fnf_amount', 12, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->boolean('finalized')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fnf_settlements');
    }
};
