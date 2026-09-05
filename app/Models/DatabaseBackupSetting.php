<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DatabaseBackupSetting extends Model
{
    protected $fillable = [
        'reminder_interval_days',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'reminder_interval_days' => 'integer',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
