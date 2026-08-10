<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Task;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class TaskAgendaService
{
    public function calendarDays(CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return collect(CarbonImmutable::parse($start)->toPeriod($end))->map(fn (CarbonImmutable $date): array => [
            'date' => $date, 'key' => $date->toDateString(), 'day' => $date->day,
        ]);
    }

    public function isActive(Task $task): bool
    {
        return ! in_array($task->status, [TaskStatus::Completed, TaskStatus::Cancelled], true);
    }

    public function overdueDays(Task $task, CarbonImmutable $today): int
    {
        return max(0, $task->due_date->diffInDays($today));
    }
}
