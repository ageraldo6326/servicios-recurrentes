<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CommercialDocumentHistory extends Model
{
    protected $fillable = ['user_id', 'action', 'data'];
    protected $casts = ['data' => 'array'];
    public function document(): MorphTo { return $this->morphTo(); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
