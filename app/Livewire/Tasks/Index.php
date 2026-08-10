<?php

namespace App\Livewire\Tasks;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Services\TaskAgendaService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    #[Url]
    public string $view = 'month';

    #[Url]
    public string $cursor = '';

    #[Url]
    public string $selected = '';

    #[Url]
    public string $search = '';

    #[Url]
    public string $filter = 'all';

    #[Url]
    public string $priorityFilter = 'all';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $description = '';

    public string $startDate = '';

    public string $dueDate = '';

    public string $scheduledTime = '';

    public string $priority = 'normal';

    public string $status = 'pending';

    public string $category = '';

    public string $notes = '';

    public string $reminderMinutes = '';

    public function mount(): void
    {
        $today = CarbonImmutable::now(config('app.timezone'));
        $this->cursor = $this->cursor ?: $today->toDateString();
        $this->selected = $this->selected ?: $today->toDateString();
    }

    public function setView(string $view): void
    {
        $this->view = in_array($view, ['month', 'week', 'day'], true) ? $view : 'month';
    }

    public function previous(): void
    {
        $this->move(-1);
    }

    public function next(): void
    {
        $this->move(1);
    }

    public function today(): void
    {
        $this->cursor = $this->selected = now(config('app.timezone'))->toDateString();
    }

    public function selectDate(string $date): void
    {
        $this->selected = $date;
        $this->cursor = $date;
    }

    public function openCreate(?string $date = null): void
    {
        $date ??= $this->selected;
        $this->resetForm();
        $this->startDate = $date;
        $this->dueDate = $date;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $task = $this->ownedTask($id);
        $this->editingId = $id;
        foreach (['title', 'description', 'category', 'notes'] as $field) {
            $this->{$field} = (string) ($task->{$field} ?? '');
        }
        $this->startDate = $task->start_date->toDateString();
        $this->dueDate = $task->due_date->toDateString();
        $this->scheduledTime = $task->scheduled_time?->format('H:i') ?? '';
        $this->priority = $task->priority->value;
        $this->status = $task->status->value;
        $this->reminderMinutes = (string) ($task->reminder_minutes ?? '');
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'title' => ['required', 'string', 'max:180'], 'description' => ['nullable', 'string', 'max:10000'],
            'startDate' => ['required', 'date'], 'dueDate' => ['required', 'date', 'after_or_equal:startDate'],
            'scheduledTime' => ['nullable', 'date_format:H:i'], 'priority' => [Rule::enum(TaskPriority::class)],
            'status' => [Rule::enum(TaskStatus::class)], 'category' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:10000'], 'reminderMinutes' => ['nullable', 'integer', 'min:0', 'max:10080'],
        ]);
        $payload = ['title' => $data['title'], 'description' => $data['description'] ?: null, 'start_date' => $data['startDate'], 'due_date' => $data['dueDate'], 'scheduled_time' => $data['scheduledTime'] ?: null, 'priority' => $data['priority'], 'status' => $data['status'], 'category' => $data['category'] ?: null, 'notes' => $data['notes'] ?: null, 'reminder_minutes' => $data['reminderMinutes'] === '' ? null : $data['reminderMinutes']];
        $task = $this->editingId ? $this->ownedTask($this->editingId) : new Task(['user_id' => auth()->id()]);
        $task->fill($payload);
        $task->save();
        $this->showForm = false;
        $this->resetForm();
    }

    public function toggleComplete(int $id): void
    {
        $task = $this->ownedTask($id);
        if ($task->status === TaskStatus::Completed) {
            $task->update(['status' => TaskStatus::Pending, 'completed_at' => null, 'completed_by' => null]);
        } else {
            $task->update(['status' => TaskStatus::Completed, 'completed_at' => now(), 'completed_by' => auth()->id()]);
        }
    }

    public function cancel(int $id): void
    {
        $this->ownedTask($id)->update(['status' => TaskStatus::Cancelled]);
    }

    public function changeStatus(int $id, string $status): void
    {
        $task = $this->ownedTask($id);
        $newStatus = TaskStatus::tryFrom($status);

        if ($newStatus === null) {
            return;
        }

        $task->update([
            'status' => $newStatus,
            'completed_at' => $newStatus === TaskStatus::Completed ? now() : null,
            'completed_by' => $newStatus === TaskStatus::Completed ? auth()->id() : null,
        ]);
    }

    public function delete(int $id): void
    {
        $this->ownedTask($id)->delete();
    }

    public function reschedule(int $id, string $date): void
    {
        $task = $this->ownedTask($id);
        $task->update(['start_date' => $date, 'due_date' => $date, 'status' => TaskStatus::Pending]);
    }

    public function render(TaskAgendaService $agenda): View
    {
        $cursor = CarbonImmutable::parse($this->cursor, config('app.timezone'))->startOfDay();
        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();
        [$from, $to] = $this->range($cursor);
        $calendarTasks = $this->taskQuery($today)->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])->orderBy('due_date')->orderByRaw('scheduled_time IS NULL, scheduled_time')->get();
        $filteredTasks = $this->taskQuery($today)->orderBy('due_date')->orderByRaw('scheduled_time IS NULL, scheduled_time')->get();
        $all = Task::query()->where('user_id', auth()->id());
        $active = fn ($q) => $q->whereIn('status', [TaskStatus::Pending->value, TaskStatus::InProgress->value]);
        $stats = ['today' => (clone $all)->whereDate('due_date', $today)->where($active)->count(), 'pending' => (clone $all)->where($active)->count(), 'overdue' => (clone $all)->where($active)->whereDate('due_date', '<', $today)->count(), 'completed' => (clone $all)->where('status', TaskStatus::Completed)->count()];
        $dayTasks = Task::query()->where('user_id', auth()->id())->whereDate('due_date', $this->selected)->orderByRaw('scheduled_time IS NULL, scheduled_time')->get();
        $overdueTasks = Task::query()->where('user_id', auth()->id())->whereIn('status', [TaskStatus::Pending->value, TaskStatus::InProgress->value])->whereDate('due_date', '<', $today)->orderBy('due_date')->limit(4)->get();

        return view('livewire.tasks.index', ['tasks' => $calendarTasks, 'filteredTasks' => $filteredTasks, 'dayTasks' => $dayTasks, 'overdueTasks' => $overdueTasks, 'days' => $agenda->calendarDays($from, $to), 'stats' => $stats, 'today' => $today, 'cursorDate' => $cursor, 'categories' => Task::query()->where('user_id', auth()->id())->whereNotNull('category')->distinct()->orderBy('category')->pluck('category')]);
    }

    private function taskQuery(CarbonImmutable $today): Builder
    {
        return Task::query()->where('user_id', auth()->id())
            ->when($this->search !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q->where('title', 'like', '%'.$this->search.'%')->orWhere('description', 'like', '%'.$this->search.'%')->orWhere('notes', 'like', '%'.$this->search.'%')->orWhere('category', 'like', '%'.$this->search.'%')))
            ->when($this->category !== '', fn (Builder $q) => $q->where('category', $this->category))
            ->when($this->filter === 'active', fn (Builder $q) => $q->whereIn('status', [TaskStatus::Pending->value, TaskStatus::InProgress->value]))
            ->when($this->filter === 'completed', fn (Builder $q) => $q->where('status', TaskStatus::Completed))
            ->when($this->filter === 'overdue', fn (Builder $q) => $q->whereIn('status', [TaskStatus::Pending->value, TaskStatus::InProgress->value])->whereDate('due_date', '<', $today))
            ->when($this->filter === 'today', fn (Builder $q) => $q->whereDate('due_date', $today))
            ->when($this->priorityFilter !== 'all', fn (Builder $q) => $q->where('priority', $this->priorityFilter));
    }

    private function move(int $direction): void
    {
        $date = CarbonImmutable::parse($this->cursor, config('app.timezone'));
        $this->cursor = ($this->view === 'day' ? $date->addDays($direction) : ($this->view === 'week' ? $date->addWeeks($direction) : $date->addMonths($direction)))->toDateString();
    }

    private function range(CarbonImmutable $cursor): array
    {
        return match ($this->view) {
            'day' => [$cursor, $cursor], 'week' => [$cursor->startOfWeek(), $cursor->endOfWeek()], default => [$cursor->startOfMonth()->startOfWeek(), $cursor->endOfMonth()->endOfWeek()]
        };
    }

    private function ownedTask(int $id): Task
    {
        return Task::query()->where('user_id', auth()->id())->findOrFail($id);
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'title', 'description', 'scheduledTime', 'category', 'notes', 'reminderMinutes']);
        $this->priority = 'normal';
        $this->status = 'pending';
        $this->resetValidation();
    }
}
