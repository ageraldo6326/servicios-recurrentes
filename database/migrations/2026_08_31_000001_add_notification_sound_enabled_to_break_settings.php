<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('break_settings', function (Blueprint $table): void {
            $table->boolean('notification_sound_enabled')->default(true)->after('sound_on_return');
        });
    }

    public function down(): void
    {
        Schema::table('break_settings', function (Blueprint $table): void {
            $table->dropColumn('notification_sound_enabled');
        });
    }
};
