<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommercialInvoiceItem extends Model
{
    protected $fillable = ['concept', 'description', 'quantity', 'unit', 'unit_price', 'discount', 'tax_rate', 'total'];
    protected $casts = ['quantity' => 'decimal:2', 'unit_price' => 'decimal:2', 'discount' => 'decimal:2', 'tax_rate' => 'decimal:2', 'total' => 'decimal:2'];
    public function invoice(): BelongsTo { return $this->belongsTo(CommercialInvoice::class, 'invoice_id'); }
    public function getLineSubtotalAttribute(): float { return max(0, (float) $this->quantity * (float) $this->unit_price - (float) $this->discount); }
    public function getLineTaxAttribute(): float { return $this->line_subtotal * ((float) $this->tax_rate / 100); }
}
