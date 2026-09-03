<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_grades', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Grade code from the Staff Grading & Compensation Policy, e.g. T3-A, A2, M1');
            $table->string('description');
            $table->string('applicable_to')->nullable()->comment('Section/level this grade applies to, e.g. Pre-Primary, Primary, Office Staff');
            $table->enum('staff_type', ['teaching', 'administrative', 'management']);
            $table->decimal('min_basic', 12, 2)->nullable()->comment('Minimum monthly basic salary per policy; null where not yet finalized');
            $table->decimal('max_basic', 12, 2)->nullable();
            $table->decimal('yearly_increment', 12, 2)->nullable()->comment('Standard yearly increment to monthly basic ("average performance" quantum)');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_grades');
    }
};
