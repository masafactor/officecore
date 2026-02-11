<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->date('report_date');          // 対象日
            $table->text('content')->nullable();  // 本文（空でも保存OK）
            $table->string('status', 20)->default('draft'); // draft / submitted（今はdraft運用）

            $table->timestamps();

            $table->unique(['user_id', 'report_date']); // 1日1件
            $table->index(['user_id', 'report_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};
