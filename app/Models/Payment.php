<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = ['amount', 'currency', 'received_at', 'status', 'evidence_path', 'validated_at'];

    protected $casts = ['amount' => 'decimal:2', 'received_at' => 'date', 'validated_at' => 'datetime', 'status' => PaymentStatus::class];

    public function charges(): BelongsToMany
    {
        return $this->belongsToMany(Charge::class, 'payment_allocations')->withPivot(['amount', 'currency'])->withTimestamps();
    }
}
