<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_commitments', function (Blueprint $table): void {
            $table->unsignedSmallInteger('activation_days_before_due')
                ->nullable()
                ->default(15)
                ->after('due_day');
        });
    }

    public function down(): void
    {
        Schema::table('financial_commitments', function (Blueprint $table): void {
            $table->dropColumn('activation_days_before_due');
        });
    }
};
