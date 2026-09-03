<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_sync_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_sync_log_id')->constrained()->cascadeOnDelete();
            $table->string('external_employee_code')->nullable();
            $table->json('payload')->nullable();
            $table->text('error_message');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_sync_exceptions');
    }
};
