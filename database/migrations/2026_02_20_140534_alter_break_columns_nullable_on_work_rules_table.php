<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_rules', function (Blueprint $table) {
            $table->time('break_start')->nullable()->change();
            $table->time('break_end')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('work_rules', function (Blueprint $table) {
            $table->time('break_start')->nullable(false)->change();
            $table->time('break_end')->nullable(false)->change();
        });
    }
};
