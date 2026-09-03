<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->enum('location_tier', ['tier_1', 'tier_2', 'tier_3'])->nullable()->after('address')
                ->comment('Compensation Policy Annexure B-2 location category, drives HRA/CCA % of basic');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('location_tier');
        });
    }
};
