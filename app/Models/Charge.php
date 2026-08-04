<?php

namespace App\Models;

use App\Enums\ChargeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Charge extends Model
{
    use HasFactory;

    protected $fillable = ['contracted_service_id', 'status', 'amount', 'currency', 'due_date'];

    protected $casts = ['amount' => 'decimal:2', 'due_date' => 'date', 'status' => ChargeStatus::class];

    public function contractedService(): BelongsTo
    {
        return $this->belongsTo(ContractedService::class);
    }

    public function payments(): BelongsToMany
    {
        return $this->belongsToMany(Payment::class, 'payment_allocations')->withPivot(['amount', 'currency'])->withTimestamps();
    }
}
