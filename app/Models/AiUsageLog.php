<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AiUsageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'origin',
        'analysis_type',
        'model',
        'input_characters',
        'output_characters',
        'estimated_tokens',
        'estimated_cost',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'input_characters' => 'integer',
            'output_characters' => 'integer',
            'estimated_tokens' => 'integer',
            'estimated_cost' => 'decimal:6',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
