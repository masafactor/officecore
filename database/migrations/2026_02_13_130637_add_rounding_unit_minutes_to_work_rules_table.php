<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('work_rules', function (Blueprint $table) {
            $table->unsignedSmallInteger('rounding_unit_minutes')
                ->default(10) // 10 or 15 を想定
                ->after('break_end');
        });
    }

    public function down(): void
    {
        Schema::table('work_rules', function (Blueprint $table) {
            $table->dropColumn('rounding_unit_minutes');
        });
    }

};
