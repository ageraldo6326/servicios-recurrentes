<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'title', 'description', 'start_date', 'due_date', 'scheduled_time',
        'priority', 'status', 'category', 'notes', 'reminder_minutes', 'completed_at', 'completed_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'immutable_date', 'due_date' => 'immutable_date',
            'scheduled_time' => 'datetime:H:i', 'completed_at' => 'immutable_datetime',
            'priority' => TaskPriority::class, 'status' => TaskStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function isOverdue(?CarbonInterface $today = null): bool
    {
        return $this->status !== TaskStatus::Completed && $this->status !== TaskStatus::Cancelled
            && $this->due_date->lt($today ?: now(config('app.timezone'))->startOfDay());
    }
}
