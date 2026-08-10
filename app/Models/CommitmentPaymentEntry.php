<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommitmentPaymentEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'commitment_payment_id',
        'paid_at',
        'amount',
        'observations',
        'receipt_path',
    ];

    protected $casts = [
        'paid_at' => 'date',
        'amount' => 'decimal:2',
    ];

    public function commitmentPayment(): BelongsTo
    {
        return $this->belongsTo(CommitmentPayment::class);
    }
}
