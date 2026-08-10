<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('break_settings', function (Blueprint $table): void {
            $table->string('custom_break_sound_path')->nullable()->after('custom_sound_path');
        });
    }

    public function down(): void
    {
        Schema::table('break_settings', function (Blueprint $table): void {
            $table->dropColumn('custom_break_sound_path');
        });
    }
};
