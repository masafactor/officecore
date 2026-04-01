<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_calendar_days', function (Blueprint $table) {
            $table->id();
            $table->date('calendar_date')->unique();
            $table->string('day_type', 20)->default('workday'); // workday / holiday / shortday
            $table->integer('scheduled_minutes')->default(480);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['calendar_date', 'day_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_calendar_days');
    }
};