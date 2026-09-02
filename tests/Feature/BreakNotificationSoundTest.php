<?php

namespace Tests\Feature;

use App\Enums\BreakCycleStatus;
use App\Livewire\Breaks\GlobalCycle;
use App\Models\BreakSession;
use App\Models\BreakSetting;
use App\Models\User;
use App\Services\BreakCycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BreakNotificationSoundTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_toggle_the_persistent_notification_sound_preference(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(GlobalCycle::class)
            ->call('toggleNotificationSound')
            ->assertSet('notificationSoundEnabled', false);

        $setting = BreakSetting::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertFalse($setting->notification_sound_enabled);
    }

    public function test_user_can_re_enable_the_persistent_notification_sound_preference(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(GlobalCycle::class)
            ->call('toggleNotificationSound')
            ->assertSet('notificationSoundEnabled', false)
            ->call('toggleNotificationSound')
            ->assertSet('notificationSoundEnabled', true);

        $setting = BreakSetting::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertTrue($setting->notification_sound_enabled);
    }

    public function test_silencing_sound_keeps_the_break_transition_as_a_visual_alert(): void
    {
        $user = User::factory()->create();
        $cycle = app(BreakCycleService::class);
        $settings = $cycle->settings($user);
        $settings->update(['notification_sound_enabled' => false, 'sound_on_break' => true]);

        BreakSession::query()->create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'scheduled_at' => now()->subMinute(),
            'configured_work_minutes' => 30,
            'configured_break_minutes' => 5,
            'status' => BreakCycleStatus::Working,
        ]);

        $snapshot = $cycle->synchronize($user);

        $this->assertSame('break-start-visual', $snapshot['event']);
    }
}
