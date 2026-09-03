<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('type', ['earning', 'deduction']);
            $table->enum('calculation_type', ['fixed', 'percent_of_basic'])->default('fixed');
            $table->decimal('default_percentage', 5, 2)->nullable();
            $table->boolean('is_statutory')->default(false)
                ->comment('True for PF/ESI/TDS/PT/LWF components computed by StatutoryComputationService');
            $table->string('statutory_type', 8)->nullable()->comment('PF|ESI|TDS|PT|LWF when is_statutory=true');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_components');
    }
};
