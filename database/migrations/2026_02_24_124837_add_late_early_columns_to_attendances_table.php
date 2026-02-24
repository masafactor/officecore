<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->boolean('is_late')->default(false)->after('clock_in');
            $table->boolean('is_early_leave')->default(false)->after('clock_out');
            // 任意：分も保存したいなら
            // $table->unsignedInteger('late_minutes')->nullable()->after('is_late');
            // $table->unsignedInteger('early_leave_minutes')->nullable()->after('is_early_leave');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['is_late', 'is_early_leave']);
            // $table->dropColumn(['late_minutes', 'early_leave_minutes']);
        });
    }
};
