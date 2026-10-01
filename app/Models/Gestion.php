<?php

namespace App\Models;

use App\Enums\ChargeStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gestion extends Model
{
    use HasFactory;

    protected $fillable = ['client_id', 'contracted_service_id', 'charge_id', 'type', 'occurred_at', 'result', 'phone_used', 'promised_payment_date', 'next_follow_up_at', 'observations'];

    protected $casts = ['occurred_at' => 'datetime', 'promised_payment_date' => 'date', 'next_follow_up_at' => 'datetime'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function contractedService(): BelongsTo
    {
        return $this->belongsTo(ContractedService::class);
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(Charge::class);
    }

    protected function resolvedPaymentCharge(): Attribute
    {
        return Attribute::get(function (): ?Charge {
            if ($this->charge_id !== null) {
                return $this->charge;
            }

            if ($this->type !== 'Pago recibido'
                || $this->occurred_at === null
                || ! $this->relationLoaded('contractedService')
                || ! $this->contractedService->relationLoaded('charges')) {
                return null;
            }

            return $this->contractedService->charges
                ->filter(fn (Charge $charge): bool => $charge->status === ChargeStatus::Paid
                    && $charge->due_date->year === $this->occurred_at->year
                    && $charge->due_date->month === $this->occurred_at->month)
                ->sortBy(fn (Charge $charge): int => (int) abs($charge->created_at->diffInSeconds($this->occurred_at, false)))
                ->first();
        });
    }
}
