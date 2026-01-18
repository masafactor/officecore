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
        Schema::create('work_rules', function (Blueprint $table) {
        $table->id();
        $table->string('name')->unique();         // 例: 通常勤務
        $table->time('work_start');               // 09:00
        $table->time('work_end');                 // 18:00
        $table->time('break_start');              // 12:00
        $table->time('break_end');                // 13:00
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_rules');
    }
};
