<?php

declare(strict_types=1);

namespace App\Services\DatabaseBackups;

use App\Enums\DatabaseBackupRunStatus;
use App\Models\DatabaseBackupRun;
use App\Models\DatabaseBackupSetting;
use Carbon\CarbonInterface;

final class DatabaseBackupStatusService
{
    /** @return array{label: string, tone: string, last_run: ?DatabaseBackupRun, next_recommended_at: ?CarbonInterface, days_until_due: ?int, interval_days: int} */
    public function status(): array
    {
        $setting = DatabaseBackupSetting::query()->firstOrCreate(['id' => 1], ['reminder_interval_days' => 7]);
        $lastRun = DatabaseBackupRun::query()
            ->with('user')
            ->where('status', DatabaseBackupRunStatus::Completed)
            ->whereNotNull('completed_at')
            ->latest('completed_at')
            ->first();

        if ($lastRun === null) {
            return [
                'label' => 'Nunca realizado',
                'tone' => 'slate',
                'last_run' => null,
                'next_recommended_at' => null,
                'days_until_due' => null,
                'interval_days' => $setting->reminder_interval_days,
            ];
        }

        $nextRecommendedAt = $lastRun->completed_at->copy()->addDays($setting->reminder_interval_days);
        $secondsUntilDue = now()->diffInSeconds($nextRecommendedAt, false);
        $daysUntilDue = (int) ceil($secondsUntilDue / 86400);

        if ($secondsUntilDue <= 0) {
            $label = 'Pendiente';
            $tone = 'red';
        } elseif ($secondsUntilDue <= 86400) {
            $label = 'Próximo a vencer';
            $tone = 'amber';
        } else {
            $label = 'Al día';
            $tone = 'emerald';
        }

        return [
            'label' => $label,
            'tone' => $tone,
            'last_run' => $lastRun,
            'next_recommended_at' => $nextRecommendedAt,
            'days_until_due' => $daysUntilDue,
            'interval_days' => $setting->reminder_interval_days,
        ];
    }
}
