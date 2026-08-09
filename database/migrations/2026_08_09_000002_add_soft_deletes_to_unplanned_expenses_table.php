<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('unplanned_expenses', 'deleted_at')) {
            Schema::table('unplanned_expenses', function (Blueprint $table): void {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        // Intentionally left empty to preserve the audit-safe additive schema.
    }
};
