<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_salary_structure_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_salary_structure_id')
                ->constrained('employee_salary_structures')->cascadeOnDelete();
            $table->foreignId('salary_component_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2)->nullable()->comment('Used when the component is a fixed amount');
            $table->decimal('percentage', 5, 2)->nullable()->comment('Used when the component is % of basic');
            $table->timestamps();

            $table->unique(['employee_salary_structure_id', 'salary_component_id'], 'structure_component_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salary_structure_lines');
    }
};
