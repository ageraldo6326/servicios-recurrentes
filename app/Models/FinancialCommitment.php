<?php

namespace App\Models;

use App\Enums\FinancialCommitmentFrequency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialCommitment extends Model
{
    use HasFactory;

    protected $fillable = [
        'beneficiary_id',
        'name',
        'category',
        'frequency',
        'suggested_amount',
        'has_cutoff',
        'is_credit_card',
        'cutoff_day',
        'activation_days_before_due',
        'due_day',
        'payment_safety_days',
        'credit_limit',
        'current_balance',
        'statement_balance',
        'card_currency',
        'purchase_excellent_days',
        'purchase_good_days',
        'purchase_regular_days',
        'cutoff_alert_days',
        'payment_alert_days',
        'is_active',
        'cancelled_at',
        'cancelled_by_user_id',
        'cancellation_reason',
        'observations',
    ];

    protected $casts = [
        'frequency' => FinancialCommitmentFrequency::class,
        'suggested_amount' => 'decimal:2',
        'has_cutoff' => 'boolean',
        'is_credit_card' => 'boolean',
        'payment_safety_days' => 'integer',
        'credit_limit' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'statement_balance' => 'decimal:2',
        'purchase_excellent_days' => 'integer',
        'purchase_good_days' => 'integer',
        'purchase_regular_days' => 'integer',
        'activation_days_before_due' => 'integer',
        'is_active' => 'boolean',
        'cancelled_at' => 'date',
    ];

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CommitmentPayment::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    public function isCreditCard(): bool
    {
        return $this->is_credit_card || str($this->category)->lower()->contains('tarjeta');
    }
}
