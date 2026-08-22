<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->json('sidebar_menu_order')->nullable();
        });
    }

    public function down(): void
    {
        // Intencionalmente no se elimina la preferencia para proteger configuraciones guardadas.
    }
};
