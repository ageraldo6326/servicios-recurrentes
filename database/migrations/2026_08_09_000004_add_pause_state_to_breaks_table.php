<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('breaks', function (Blueprint $table): void {
            if (! Schema::hasColumn('breaks', 'paused_at')) {
                $table->dateTime('paused_at')->nullable();
            }

            if (! Schema::hasColumn('breaks', 'paused_remaining_seconds')) {
                $table->unsignedInteger('paused_remaining_seconds')->nullable();
            }

            if (! Schema::hasColumn('breaks', 'resumed_at')) {
                $table->dateTime('resumed_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Intentionally left empty to preserve the additive pause-state schema.
    }
};
