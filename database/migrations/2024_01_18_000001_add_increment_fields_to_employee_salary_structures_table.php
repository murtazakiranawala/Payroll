<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_salary_structures', function (Blueprint $table) {
            $table->string('performance_rating')->nullable()->after('basic')
                ->comment('Set only when this structure was created via the increment workflow: below_average / average / above_average');
            $table->text('remarks')->nullable()->after('performance_rating');
        });
    }

    public function down(): void
    {
        Schema::table('employee_salary_structures', function (Blueprint $table) {
            $table->dropColumn(['performance_rating', 'remarks']);
        });
    }
};
