<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerCallActivityReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'contracted_service_id',
        'received_at',
        'reported_last_outbound_at',
        'updated_last_outbound',
        'source_ip',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'immutable_datetime',
            'reported_last_outbound_at' => 'immutable_datetime',
            'updated_last_outbound' => 'boolean',
        ];
    }

    public function contractedService(): BelongsTo
    {
        return $this->belongsTo(ContractedService::class);
    }
}
