<?php

namespace App\Models;

use App\Enums\CommitmentPaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommitmentPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'financial_commitment_id',
        'period_start',
        'due_date',
        'status',
        'paid_at',
        'amount_paid',
        'observations',
        'receipt_path',
    ];

    protected $casts = [
        'period_start' => 'date',
        'due_date' => 'date',
        'status' => CommitmentPaymentStatus::class,
        'paid_at' => 'date',
        'amount_paid' => 'decimal:2',
    ];

    public function financialCommitment(): BelongsTo
    {
        return $this->belongsTo(FinancialCommitment::class);
    }
}
