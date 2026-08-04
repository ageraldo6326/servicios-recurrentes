<?php

namespace App\Models;

use App\Enums\ProviderInvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderInvoice extends Model
{
    use HasFactory;

    protected $fillable = ['provider_id', 'amount', 'currency', 'due_date', 'status', 'observations'];

    protected $casts = ['amount' => 'decimal:2', 'due_date' => 'date', 'status' => ProviderInvoiceStatus::class];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
