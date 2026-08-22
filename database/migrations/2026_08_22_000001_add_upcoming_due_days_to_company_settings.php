<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table): void {
            $table->unsignedTinyInteger('upcoming_due_days')->default(7)->after('timezone');
        });
    }

    public function down(): void
    {
        // Intencionalmente no se elimina la columna para proteger configuraciones ya registradas.
    }
};
