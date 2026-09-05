<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('can_manage_database_backups')->default(false)->after('sidebar_menu_order');
        });

        // El sistema no tenía roles: se conserva el acceso de las cuentas operativas existentes.
        DB::table('users')->update(['can_manage_database_backups' => true]);
    }

    public function down(): void
    {
        // El permiso se conserva para no eliminar configuraciones de usuarios existentes.
    }
};
