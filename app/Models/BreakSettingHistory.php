<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BreakSettingHistory extends Model
{
    protected $fillable = ['break_setting_id', 'user_id', 'action', 'data'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }
}
