<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gl_account_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained()->cascadeOnDelete()
                ->comment('Null = default mapping applied to every school unless overridden');
            $table->string('category')
                ->comment('salary_expense|pf_employer_expense|esi_employer_expense|lwf_employer_expense|reimbursement_expense|fnf_expense|pf_liability|esi_liability|tds_payable|pt_payable|lwf_liability|net_pay_payable');
            $table->string('gl_account_code');
            $table->string('cost_centre_code')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gl_account_mappings');
    }
};
