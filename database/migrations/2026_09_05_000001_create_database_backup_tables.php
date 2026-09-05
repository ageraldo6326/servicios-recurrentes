<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('database_backup_settings')) {
            Schema::create('database_backup_settings', function (Blueprint $table): void {
                $table->id();
                $table->unsignedSmallInteger('reminder_interval_days')->default(7);
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('database_backup_runs')) {
            Schema::create('database_backup_runs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
                $table->string('status', 20);
                $table->string('file_name')->nullable();
                $table->unsignedBigInteger('file_size_bytes')->nullable();
                $table->timestamp('started_at');
                $table->timestamp('completed_at')->nullable();
                $table->unsignedInteger('duration_seconds')->nullable();
                $table->string('error_code', 80)->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();

                $table->index(['status', 'completed_at']);
                $table->index(['user_id', 'started_at']);
            });
        }

        if (! Schema::hasTable('database_backup_setting_histories')) {
            Schema::create('database_backup_setting_histories', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('database_backup_setting_id');
                $table->foreignId('user_id');
                $table->unsignedSmallInteger('previous_reminder_interval_days');
                $table->unsignedSmallInteger('reminder_interval_days');
                $table->timestamps();

            });
        }

        Schema::table('database_backup_setting_histories', function (Blueprint $table): void {
            $table->index(
                ['database_backup_setting_id', 'created_at'],
                'db_bkp_setting_history_created_idx',
            );
            $table->foreign('database_backup_setting_id', 'db_bkp_setting_history_setting_fk')
                ->references('id')
                ->on('database_backup_settings')
                ->restrictOnDelete();
            $table->foreign('user_id', 'db_bkp_setting_history_user_fk')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        // La política del proyecto protege el historial y evita operaciones destructivas en rollback.
    }
};
