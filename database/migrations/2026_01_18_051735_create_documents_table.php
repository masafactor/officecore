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
        Schema::create('documents', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->string('url');
        $table->text('description')->nullable();

        $table->enum('visibility', ['all', 'department'])->default('all');

        $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
        $table->foreignId('created_by')->constrained('users')->restrictOnDelete();

        $table->timestamps();

        $table->index(['visibility']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
