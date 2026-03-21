<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_employments', function (Blueprint $table) {
            $table->foreignId('wage_table_id')
                ->nullable()
                ->after('employment_type_id')
                ->constrained('wage_tables')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_employments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('wage_table_id');
        });
    }
};