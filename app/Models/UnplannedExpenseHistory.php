<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnplannedExpenseHistory extends Model
{
    protected $fillable = [
        'unplanned_expense_id',
        'user_id',
        'action',
        'data',
    ];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(UnplannedExpense::class, 'unplanned_expense_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
