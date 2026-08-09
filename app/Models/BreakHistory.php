<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BreakHistory extends Model
{
    protected $fillable = ['break_id', 'user_id', 'action', 'data'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    public function breakSession(): BelongsTo
    {
        return $this->belongsTo(BreakSession::class, 'break_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
