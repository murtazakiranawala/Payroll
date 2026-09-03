<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replaced by direct pf_applicable/esi_applicable/pt_applicable/lwf_applicable
 * booleans on employees (see the following migration) - a date-ranged
 * exemption record was more machinery than the plain per-employee Yes/No
 * the business actually needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('employee_statutory_exemptions');
    }

    public function down(): void
    {
        Schema::create('employee_statutory_exemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->enum('statutory_type', ['PF', 'ESI', 'TDS', 'PT', 'LWF']);
            $table->text('reason')->nullable();
            $table->foreignId('exempted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'statutory_type', 'effective_from'], 'employee_exemption_unique');
        });
    }
};
