<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_cycle_id')->constrained()->cascadeOnDelete();
            $table->string('voucher_number')->unique();
            $table->enum('status', ['draft', 'posted', 'reversed'])->default('draft');
            $table->string('idempotency_key')->unique()
                ->comment('BRD FR-3.5 duplicate-posting guard - one non-reversal voucher per payroll cycle');
            $table->string('external_reference')->nullable()
                ->comment('Voucher/document reference returned by the Financial ERP once posted');
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversal_of_voucher_id')->nullable()->constrained('journal_vouchers')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_vouchers');
    }
};
