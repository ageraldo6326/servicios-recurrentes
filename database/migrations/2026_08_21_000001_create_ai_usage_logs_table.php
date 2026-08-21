<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('origin', 80);
            $table->string('analysis_type', 40);
            $table->string('model', 100);
            $table->unsignedInteger('input_characters');
            $table->unsignedInteger('output_characters')->default(0);
            $table->unsignedInteger('estimated_tokens')->nullable();
            $table->decimal('estimated_cost', 12, 6)->nullable();
            $table->string('status', 20);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        // La política del proyecto prohíbe eliminar tablas o historial, incluso durante rollback.
        // Esta migración es deliberadamente solo aditiva.
    }
};
