<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerCallActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'contracted_service_id',
        'last_outbound_at',
        'last_reported_at',
    ];

    protected function casts(): array
    {
        return [
            'last_outbound_at' => 'immutable_datetime',
            'last_reported_at' => 'immutable_datetime',
        ];
    }

    public function contractedService(): BelongsTo
    {
        return $this->belongsTo(ContractedService::class);
    }
}
