<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('external_employee_code')->nullable()
                ->comment('Employee ID/Code as received from the AIIMS Central ERP');
            $table->string('name');
            $table->string('designation')->nullable();
            $table->string('department')->nullable();
            $table->enum('category', ['teaching', 'non_teaching', 'administrative', 'support', 'other'])
                ->default('other');
            $table->date('date_of_joining')->nullable();
            $table->date('date_of_exit')->nullable();
            $table->enum('employment_status', ['active', 'on_leave', 'exited'])->default('active');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('bank_account_number')->nullable();
            $table->string('bank_ifsc')->nullable();
            $table->string('bank_name')->nullable();
            $table->text('pan')->nullable();
            $table->string('uan_number')->nullable();
            $table->string('esi_number')->nullable();
            $table->string('photo_path')->nullable();
            $table->enum('source', ['aiims_sync', 'manual'])->default('manual');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['school_id', 'external_employee_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
