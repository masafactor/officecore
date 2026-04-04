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
        Schema::table('company_calendar_days', function (Blueprint $table) {
            $table->boolean('is_public_holiday')
                ->default(false)
                ->after('day_type');

            $table->string('holiday_name')
                ->nullable()
                ->after('is_public_holiday');

            $table->index('is_public_holiday');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_calendar_days', function (Blueprint $table) {
            $table->dropIndex(['is_public_holiday']);
            $table->dropColumn(['is_public_holiday', 'holiday_name']);
        });
    }
};