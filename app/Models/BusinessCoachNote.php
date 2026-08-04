<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BusinessCoachNote extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'page', 'content'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
