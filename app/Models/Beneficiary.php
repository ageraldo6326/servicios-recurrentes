<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Beneficiary extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'is_active', 'observations'];

    protected $casts = ['is_active' => 'boolean'];

    public function financialCommitments(): HasMany
    {
        return $this->hasMany(FinancialCommitment::class);
    }
}
