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
        'cutoff_day',
        'activation_days_before_due',
        'due_day',
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
}
