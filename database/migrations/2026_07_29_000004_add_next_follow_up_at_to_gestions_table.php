<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gestions', function (Blueprint $table): void {
            $table->dateTime('next_follow_up_at')->nullable()->after('promised_payment_date');
        });
    }

    public function down(): void
    {
        Schema::table('gestions', function (Blueprint $table): void {
            $table->dropColumn('next_follow_up_at');
        });
    }
};
