<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commercial_invoices', function (Blueprint $table): void {
            $table->timestamp('paid_at')->nullable()->after('status');
            $table->index('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('commercial_invoices', function (Blueprint $table): void {
            $table->dropIndex(['paid_at']);
            $table->dropColumn('paid_at');
        });
    }
};
