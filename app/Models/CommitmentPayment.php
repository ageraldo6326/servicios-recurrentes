<?php

namespace App\Models;

use App\Enums\CommitmentPaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommitmentPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'financial_commitment_id',
        'period_start',
        'cutoff_date',
        'due_date',
        'expected_amount',
        'status',
        'paid_at',
        'amount_paid',
        'observations',
        'receipt_path',
    ];

    protected $casts = [
        'period_start' => 'date',
        'cutoff_date' => 'date',
        'due_date' => 'date',
        'expected_amount' => 'decimal:2',
        'status' => CommitmentPaymentStatus::class,
        'paid_at' => 'date',
        'amount_paid' => 'decimal:2',
    ];

    public function financialCommitment(): BelongsTo
    {
        return $this->belongsTo(FinancialCommitment::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(CommitmentPaymentEntry::class);
    }
}
