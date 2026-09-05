<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DatabaseBackupSetting;
use App\Models\DatabaseBackupSettingHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ConfigureDatabaseBackupSetting
{
    public function execute(User $user, int $reminderIntervalDays): DatabaseBackupSetting
    {
        return DB::transaction(function () use ($user, $reminderIntervalDays): DatabaseBackupSetting {
            $setting = DatabaseBackupSetting::query()->firstOrCreate(
                ['id' => 1],
                ['reminder_interval_days' => 7, 'updated_by' => $user->id],
            );

            if ($setting->reminder_interval_days !== $reminderIntervalDays) {
                DatabaseBackupSettingHistory::query()->create([
                    'database_backup_setting_id' => $setting->id,
                    'user_id' => $user->id,
                    'previous_reminder_interval_days' => $setting->reminder_interval_days,
                    'reminder_interval_days' => $reminderIntervalDays,
                ]);

                $setting->update([
                    'reminder_interval_days' => $reminderIntervalDays,
                    'updated_by' => $user->id,
                ]);
            }

            return $setting->refresh();
        });
    }
}
