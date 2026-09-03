<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->boolean('pf_applicable')->default(true)->after('esi_number');
            $table->boolean('esi_applicable')->default(true)->after('pf_applicable');
            $table->boolean('pt_applicable')->default(true)->after('esi_applicable');
            $table->boolean('lwf_applicable')->default(true)->after('pt_applicable');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['pf_applicable', 'esi_applicable', 'pt_applicable', 'lwf_applicable']);
        });
    }
};
