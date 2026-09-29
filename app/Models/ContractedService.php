<?php

namespace App\Models;

use App\Enums\CallMonitoringPlatform;
use App\Enums\ContractedServiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ContractedService extends Model
{
    use HasFactory;

    protected $fillable = ['client_id', 'catalog_service_id', 'provider_id', 'price', 'price_currency', 'cost', 'cost_currency', 'ip', 'call_monitoring_platform', 'call_monitoring_enabled', 'inactivity_threshold_hours', 'report_delay_threshold_hours', 'billing_day', 'status', 'starts_at', 'cancelled_at', 'cancellation_reason', 'observations'];

    protected $casts = ['price' => 'decimal:2', 'cost' => 'decimal:2', 'starts_at' => 'date', 'cancelled_at' => 'datetime', 'status' => ContractedServiceStatus::class, 'call_monitoring_platform' => CallMonitoringPlatform::class, 'call_monitoring_enabled' => 'boolean', 'inactivity_threshold_hours' => 'integer', 'report_delay_threshold_hours' => 'integer'];

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

    public function callActivity(): HasOne
    {
        return $this->hasOne(ServerCallActivity::class);
    }

    public function callActivityReports(): HasMany
    {
        return $this->hasMany(ServerCallActivityReport::class);
    }
}
