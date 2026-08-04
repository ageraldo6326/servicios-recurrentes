<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gestion extends Model
{
    use HasFactory;

    protected $fillable = ['client_id', 'contracted_service_id', 'type', 'occurred_at', 'result', 'phone_used', 'promised_payment_date', 'next_follow_up_at', 'observations'];

    protected $casts = ['occurred_at' => 'datetime', 'promised_payment_date' => 'date', 'next_follow_up_at' => 'datetime'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function contractedService(): BelongsTo
    {
        return $this->belongsTo(ContractedService::class);
    }
}
