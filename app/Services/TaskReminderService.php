<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonImmutable;

final class TaskReminderService
{
    /** @return array{task: ?Task, target_at: ?string, token: ?string} */
    public function nextFor(User $user): array
    {
        $now = CarbonImmutable::now(config('app.timezone'));
        $task = Task::query()
            ->where('user_id', $user->id)
            ->whereDate('due_date', '>=', $now->toDateString())
            ->whereNotNull('scheduled_time')
            ->whereNotNull('reminder_minutes')
            ->whereNotIn('status', [TaskStatus::Completed->value, TaskStatus::Cancelled->value])
            ->orderBy('due_date')
            ->orderBy('scheduled_time')
            ->get()
            ->map(fn (Task $candidate): array => [$candidate, $this->reminderAt($candidate)])
            ->sortBy(fn (array $entry): int => $entry[1]->getTimestamp())
            ->first();

        if ($task === null) {
            return ['task' => null, 'target_at' => null, 'token' => null];
        }

        [$model, $target] = $task;

        return [
            'task' => $model,
            'target_at' => $target->toIso8601String(),
            'token' => $model->id.':'.$target->timestamp,
        ];
    }

    private function reminderAt(Task $task): CarbonImmutable
    {
        $scheduled = CarbonImmutable::createFromFormat(
            'Y-m-d H:i',
            $task->due_date->format('Y-m-d').' '.$task->scheduled_time->format('H:i'),
            config('app.timezone'),
        );

        return $scheduled->subMinutes((int) $task->reminder_minutes);
    }
}
