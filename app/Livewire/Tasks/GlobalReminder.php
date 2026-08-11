<?php

namespace App\Livewire\Tasks;

use App\Services\TaskReminderService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class GlobalReminder extends Component
{
    public ?int $taskId = null;

    public ?string $taskTitle = null;

    public ?string $targetAt = null;

    public ?string $token = null;

    public function mount(TaskReminderService $reminders): void
    {
        $this->refreshReminder($reminders);
    }

    public function tick(TaskReminderService $reminders): void
    {
        $this->refreshReminder($reminders);
    }

    public function render(): View
    {
        return view('livewire.tasks.global-reminder');
    }

    private function refreshReminder(TaskReminderService $reminders): void
    {
        $next = $reminders->nextFor(auth()->user());
        $this->taskId = $next['task']?->id;
        $this->taskTitle = $next['task']?->title;
        $this->targetAt = $next['target_at'];
        $this->token = $next['token'];
    }
}
