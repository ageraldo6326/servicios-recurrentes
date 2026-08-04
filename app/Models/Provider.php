<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'payment_method', 'accepts_partial_payments', 'observations'];

    protected $casts = ['accepts_partial_payments' => 'boolean'];

    public function contractedServices(): HasMany
    {
        return $this->hasMany(ContractedService::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(ProviderInvoice::class);
    }
}
