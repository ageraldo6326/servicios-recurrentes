<?php

namespace App\Livewire\Breaks;

use App\Services\BreakCycleService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class GlobalCycle extends Component
{
    public string $status = 'idle';

    public string $statusLabel = 'Sin iniciar';

    public ?string $targetAt = null;

    public ?string $customSoundUrl = null;

    public ?string $customBreakSoundUrl = null;

    public ?int $sessionId = null;

    public int $remainingSeconds = 0;

    public int $completedToday = 0;

    public int $cancelledToday = 0;

    public int $restSecondsToday = 0;

    public bool $enabled = true;

    public bool $soundOnBreak = true;

    public bool $soundOnReturn = true;

    public bool $visualAlert = true;

    public ?string $exerciseName = null;

    public ?string $exerciseDescription = null;

    public ?string $exerciseInstructions = null;

    public int $breakMinutes = 5;

    public int $workMinutes = 30;

    public function mount(): void
    {
        $this->tick();
    }

    public function tick(): void
    {
        $cycle = app(BreakCycleService::class);
        $snapshot = $cycle->synchronize(auth()->user());
        $this->refreshSnapshot($snapshot);

        if ($snapshot['event'] !== null) {
            $this->dispatch('break-cycle-alert', kind: $snapshot['event'], token: $snapshot['event_key']);
        }
    }

    public function startWork(): void
    {
        $cycle = app(BreakCycleService::class);
        $cycle->startWork(auth()->user());
        $this->tick();
    }

    public function takeBreak(): void
    {
        $cycle = app(BreakCycleService::class);
        $cycle->acceptBreak(auth()->user());
        $this->tick();
    }

    public function cancelBreak(): void
    {
        $cycle = app(BreakCycleService::class);
        $cycle->cancelBreak(auth()->user());
        $this->tick();
    }

    public function cancelActiveBreak(): void
    {
        app(BreakCycleService::class)->cancelActiveBreak(auth()->user());
        $this->tick();
    }

    public function pauseWork(): void
    {
        app(BreakCycleService::class)->pauseWork(auth()->user());
        $this->tick();
    }

    public function resumeWork(): void
    {
        app(BreakCycleService::class)->resumeWork(auth()->user());
        $this->tick();
    }

    public function render(): View
    {
        return view('livewire.breaks.global-cycle');
    }

    /** @param array<string, mixed> $snapshot */
    private function refreshSnapshot(array $snapshot): void
    {
        $this->status = $snapshot['status'];
        $this->statusLabel = $snapshot['status_label'];
        $this->targetAt = $snapshot['target_at'];
        $this->customSoundUrl = $snapshot['custom_sound_url'];
        $this->customBreakSoundUrl = $snapshot['custom_break_sound_url'];
        $this->sessionId = $snapshot['session']?->id;
        $this->remainingSeconds = $snapshot['remaining_seconds'];
        $this->completedToday = $snapshot['completed_today'];
        $this->cancelledToday = $snapshot['cancelled_today'];
        $this->restSecondsToday = $snapshot['rest_seconds_today'];
        $this->enabled = $snapshot['settings']->is_enabled;
        $this->soundOnBreak = $snapshot['settings']->sound_on_break;
        $this->soundOnReturn = $snapshot['settings']->sound_on_return;
        $this->visualAlert = $snapshot['settings']->visual_alert;
        $this->breakMinutes = $snapshot['settings']->break_minutes;
        $this->workMinutes = $snapshot['settings']->work_minutes;
        $this->exerciseName = $snapshot['session']?->exercise?->name;
        $this->exerciseDescription = $snapshot['session']?->exercise?->description;
        $this->exerciseInstructions = $snapshot['session']?->exercise?->instructions;
    }
}
