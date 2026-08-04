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
        'due_day',
        'is_active',
        'observations',
    ];

    protected $casts = [
        'frequency' => FinancialCommitmentFrequency::class,
        'suggested_amount' => 'decimal:2',
        'has_cutoff' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CommitmentPayment::class);
    }
}
