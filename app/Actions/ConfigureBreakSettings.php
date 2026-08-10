<?php

namespace App\Actions;

use App\Models\BreakSetting;
use App\Models\BreakSettingHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ConfigureBreakSettings
{
    public function execute(User $user, array $attributes): BreakSetting
    {
        return DB::transaction(function () use ($user, $attributes): BreakSetting {
            $setting = BreakSetting::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                    'is_enabled' => true,
                    'work_minutes' => 30,
                    'break_minutes' => 5,
                    'sound_on_break' => true,
                    'sound_on_return' => true,
                    'custom_sound_path' => null,
                    'custom_break_sound_path' => null,
                    'visual_alert' => true,
                ],
            );

            $setting->fill([...$attributes, 'updated_by' => $user->id]);
            $setting->save();

            BreakSettingHistory::query()->create([
                'break_setting_id' => $setting->id,
                'user_id' => $user->id,
                'action' => 'updated',
                'data' => $setting->toArray(),
            ]);

            return $setting->refresh();
        });
    }
}
