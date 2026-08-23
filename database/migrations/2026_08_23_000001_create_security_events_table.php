<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event_type', 50);
            $table->string('severity', 12);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('route', 160)->nullable();
            $table->string('method', 10)->nullable();
            $table->string('user_agent', 1000)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index('event_type');
            $table->index('severity');
            $table->index('ip_address');
            $table->index(['user_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        // La política del proyecto prohíbe eliminar tablas o historial, incluso durante rollback.
    }
};
