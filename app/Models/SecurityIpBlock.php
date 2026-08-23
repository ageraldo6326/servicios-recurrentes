<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SecurityIpBlockScope;
use App\Enums\SecurityIpBlockSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SecurityIpBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'scope',
        'source',
        'reason',
        'blocked_at',
        'expires_at',
        'released_at',
        'released_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'scope' => SecurityIpBlockScope::class,
            'source' => SecurityIpBlockSource::class,
            'blocked_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'released_at' => 'immutable_datetime',
            'metadata' => 'array',
        ];
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('released_at')
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
