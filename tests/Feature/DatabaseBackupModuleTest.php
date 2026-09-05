<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DatabaseBackupRunStatus;
use App\Models\DatabaseBackupRun;
use App\Models\DatabaseBackupSetting;
use App\Models\User;
use App\Services\DatabaseBackups\DatabaseBackupStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DatabaseBackupModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_authorized_users_can_open_the_backup_module(): void
    {
        $unauthorizedUser = User::factory()->create(['can_manage_database_backups' => false]);
        $authorizedUser = User::factory()->create(['can_manage_database_backups' => true]);

        $this->actingAs($unauthorizedUser)
            ->get(route('database-backups.index'))
            ->assertForbidden();

        $this->actingAs($authorizedUser)
            ->get(route('database-backups.index'))
            ->assertOk()
            ->assertSee('Respaldo de base de datos')
            ->assertSee('Generando respaldo');
    }

    public function test_reminder_interval_is_validated_and_audited(): void
    {
        $user = User::factory()->create(['can_manage_database_backups' => true]);

        $this->actingAs($user)
            ->put(route('database-backups.update'), ['reminder_interval_days' => 0])
            ->assertSessionHasErrors('reminder_interval_days');

        $this->actingAs($user)
            ->put(route('database-backups.update'), ['reminder_interval_days' => 14])
            ->assertRedirect();

        $this->assertDatabaseHas('database_backup_settings', ['id' => 1, 'reminder_interval_days' => 14, 'updated_by' => $user->id]);
        $this->assertDatabaseHas('database_backup_setting_histories', [
            'user_id' => $user->id,
            'previous_reminder_interval_days' => 7,
            'reminder_interval_days' => 14,
        ]);
    }

    public function test_failed_runs_do_not_reset_the_pending_reminder(): void
    {
        $user = User::factory()->create(['can_manage_database_backups' => true]);
        DatabaseBackupSetting::query()->create(['id' => 1, 'reminder_interval_days' => 7, 'updated_by' => $user->id]);
        DatabaseBackupRun::query()->create([
            'user_id' => $user->id,
            'status' => DatabaseBackupRunStatus::Completed,
            'file_name' => 'backup.sql.gz',
            'file_size_bytes' => 123,
            'started_at' => now()->subDays(10),
            'completed_at' => now()->subDays(10),
            'duration_seconds' => 1,
        ]);
        DatabaseBackupRun::query()->create([
            'user_id' => $user->id,
            'status' => DatabaseBackupRunStatus::Failed,
            'started_at' => now()->subDay(),
            'completed_at' => now()->subDay(),
            'duration_seconds' => 1,
            'error_code' => 'dump_failed',
            'error_message' => 'No fue posible generar el respaldo.',
        ]);

        $status = app(DatabaseBackupStatusService::class)->status();

        $this->assertSame('Pendiente', $status['label']);
        $this->assertSame($user->id, $status['last_run']->user_id);
        $this->assertLessThan(0, $status['days_until_due']);
    }
}
