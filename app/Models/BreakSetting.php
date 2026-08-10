<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BreakSetting extends Model
{
    protected $fillable = [
        'user_id', 'is_enabled', 'work_minutes', 'break_minutes',
        'sound_on_break', 'sound_on_return', 'visual_alert', 'created_by', 'updated_by',
        'custom_sound_path',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'sound_on_break' => 'boolean',
            'sound_on_return' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(BreakSettingHistory::class);
    }
}
