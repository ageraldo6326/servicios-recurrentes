<?php

namespace App\Models;

use App\Enums\ContractedServiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractedService extends Model
{
    use HasFactory;

    protected $fillable = ['client_id', 'catalog_service_id', 'provider_id', 'price', 'price_currency', 'cost', 'cost_currency', 'ip', 'billing_day', 'status', 'starts_at', 'cancelled_at', 'cancellation_reason', 'observations'];

    protected $casts = ['price' => 'decimal:2', 'cost' => 'decimal:2', 'starts_at' => 'date', 'cancelled_at' => 'datetime', 'status' => ContractedServiceStatus::class];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function catalogService(): BelongsTo
    {
        return $this->belongsTo(CatalogService::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(Charge::class);
    }

    public function gestions(): HasMany
    {
        return $this->hasMany(Gestion::class);
    }
}
