<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'phone', 'contact_name', 'contact_position', 'commercial_email', 'commercial_phone', 'commercial_address', 'city', 'province', 'country', 'tax_id', 'payment_terms', 'preferred_currency', 'commercial_notes'];

    public function commercialQuotes(): HasMany { return $this->hasMany(CommercialQuote::class); }
    public function commercialInvoices(): HasMany { return $this->hasMany(CommercialInvoice::class); }

    public function contractedServices(): HasMany
    {
        return $this->hasMany(ContractedService::class);
    }

    public function gestions(): HasMany
    {
        return $this->hasMany(Gestion::class);
    }
}
