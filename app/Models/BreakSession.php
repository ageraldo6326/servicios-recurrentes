<?php

namespace App\Models;

use App\Enums\BreakCycleStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BreakSession extends Model
{
    protected $table = 'breaks';

    protected $fillable = [
        'user_id', 'exercise_id', 'created_by', 'updated_by', 'scheduled_at', 'notified_at',
        'accepted_at', 'cancelled_at', 'started_at', 'ended_at', 'returned_to_work_at',
        'paused_at', 'paused_remaining_seconds', 'resumed_at', 'configured_work_minutes',
        'configured_break_minutes', 'actual_duration_seconds', 'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => BreakCycleStatus::class,
            'scheduled_at' => 'immutable_datetime',
            'notified_at' => 'immutable_datetime',
            'accepted_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'started_at' => 'immutable_datetime',
            'ended_at' => 'immutable_datetime',
            'returned_to_work_at' => 'immutable_datetime',
            'paused_at' => 'immutable_datetime',
            'resumed_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(BreakExercise::class, 'exercise_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(BreakHistory::class, 'break_id');
    }
}
