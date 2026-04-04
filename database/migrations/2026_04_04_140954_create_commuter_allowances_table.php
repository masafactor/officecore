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
        Schema::create('commuter_allowances', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();

        $table->date('start_date');
        $table->date('end_date')->nullable();

        $table->string('from_place');
        $table->string('to_place');

        $table->integer('amount');
        $table->string('pass_type', 20)->default('monthly');
        // monthly / three_month / six_month

        $table->string('status', 20)->default('active');
        // active / stopped / expired

        $table->text('note')->nullable();

        $table->timestamps();

        $table->index(['user_id', 'start_date']);
        $table->index(['user_id', 'status']);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commuter_allowances');
    }
};
