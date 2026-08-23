<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_ip_blocks', function (Blueprint $table): void {
            $table->id();
            $table->string('ip_address', 45);
            $table->string('scope', 30);
            $table->string('source', 20);
            $table->string('reason', 500);
            $table->timestamp('blocked_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['ip_address', 'scope', 'released_at']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        // La política del proyecto prohíbe eliminar tablas o historial, incluso durante rollback.
    }
};
