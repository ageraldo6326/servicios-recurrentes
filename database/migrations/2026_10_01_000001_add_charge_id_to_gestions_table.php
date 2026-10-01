<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gestions', function (Blueprint $table): void {
            $table->foreignId('charge_id')
                ->nullable()
                ->after('contracted_service_id')
                ->constrained()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gestions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('charge_id');
        });
    }
};
