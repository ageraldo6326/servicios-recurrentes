<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BreakExercise extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'created_by', 'updated_by', 'name', 'description', 'instructions',
        'category', 'recommended_duration_minutes', 'difficulty', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeAvailableTo(Builder $query, int $userId): Builder
    {
        return $query->where('is_active', true)->where(function (Builder $query) use ($userId): void {
            $query->whereNull('user_id')->orWhere('user_id', $userId);
        });
    }
}
