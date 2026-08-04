<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommercialLineTemplate extends Model
{
    protected $fillable = ['created_by', 'concept', 'description', 'unit', 'unit_price'];
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
