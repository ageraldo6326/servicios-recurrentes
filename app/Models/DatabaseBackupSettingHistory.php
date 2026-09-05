<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DatabaseBackupSettingHistory extends Model
{
    protected $fillable = [
        'database_backup_setting_id',
        'user_id',
        'previous_reminder_interval_days',
        'reminder_interval_days',
    ];

    protected function casts(): array
    {
        return [
            'previous_reminder_interval_days' => 'integer',
            'reminder_interval_days' => 'integer',
        ];
    }

    public function setting(): BelongsTo
    {
        return $this->belongsTo(DatabaseBackupSetting::class, 'database_backup_setting_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
