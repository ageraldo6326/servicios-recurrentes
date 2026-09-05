<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DatabaseBackupRunStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DatabaseBackupRun extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'file_name',
        'file_size_bytes',
        'started_at',
        'completed_at',
        'duration_seconds',
        'error_code',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'status' => DatabaseBackupRunStatus::class,
            'file_size_bytes' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
