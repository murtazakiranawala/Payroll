<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statutory_rate_configs', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['PF', 'ESI', 'TDS', 'PT', 'LWF']);
            $table->string('name');
            $table->json('config')->comment('Rate/slab definition, shape depends on type - see StatutoryComputationService');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statutory_rate_configs');
    }
};
