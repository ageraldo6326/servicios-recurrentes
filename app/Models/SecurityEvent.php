<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SecurityEventSeverity;
use App\Enums\SecurityEventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SecurityEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_type',
        'severity',
        'user_id',
        'ip_address',
        'route',
        'method',
        'user_agent',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => SecurityEventType::class,
            'severity' => SecurityEventSeverity::class,
            'metadata' => 'array',
            'occurred_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
