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
        Schema::create('notices', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->text('body');

        $table->dateTime('published_at')->nullable(); // null = 下書き
        $table->boolean('is_pinned')->default(false);

        $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
        $table->timestamps();

        $table->index(['published_at']);
        $table->index(['is_pinned']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notices');
    }
};
