<?php

namespace App\Models;

use App\Enums\CommercialInvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CommercialInvoice extends Model
{
    use HasFactory;
    protected $fillable = ['client_id', 'quote_id', 'created_by', 'updated_by', 'number', 'issue_date', 'due_date', 'currency', 'discount', 'status', 'notes', 'terms', 'comments'];
    protected $casts = ['status' => CommercialInvoiceStatus::class, 'issue_date' => 'date', 'due_date' => 'date', 'discount' => 'decimal:2'];
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function quote(): BelongsTo { return $this->belongsTo(CommercialQuote::class, 'quote_id'); }
    public function items(): HasMany { return $this->hasMany(CommercialInvoiceItem::class, 'invoice_id'); }
    public function histories(): MorphMany { return $this->morphMany(CommercialDocumentHistory::class, 'document'); }
    public function getSubtotalAttribute(): float { return (float) $this->items->sum(fn ($item) => $item->line_subtotal); }
    public function getTaxTotalAttribute(): float { return (float) $this->items->sum(fn ($item) => $item->line_tax); }
    public function getTotalAttribute(): float { return max(0, $this->subtotal - (float) $this->discount + $this->tax_total); }
}
