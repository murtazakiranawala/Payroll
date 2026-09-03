<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliation_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_cycle_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('payroll_register_total', 14, 2);
            $table->decimal('financial_erp_posted_total', 14, 2);
            $table->decimal('variance', 14, 2);
            $table->enum('status', ['matched', 'variance']);
            $table->timestamp('generated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_records');
    }
};
