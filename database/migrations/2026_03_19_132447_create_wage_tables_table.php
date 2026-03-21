<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wage_tables', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employment_type_id')
                ->constrained('employment_types')
                ->cascadeOnDelete();

            $table->string('code')->unique();
            $table->string('name');
            $table->integer('hourly_wage');

            $table->date('start_date');
            $table->date('end_date')->nullable();

            $table->timestamps();

            $table->index(['employment_type_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wage_tables');
    }
};