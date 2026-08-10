<?php

namespace App\Services;

use App\Enums\BreakCycleStatus;
use App\Models\BreakExercise;
use App\Models\BreakHistory;
use App\Models\BreakSession;
use App\Models\BreakSetting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class BreakCycleService
{
    /** @var list<string> */
    private const ACTIVE_STATUSES = [
        'working', 'break_pending', 'break_active', 'break_completed', 'break_cancelled', 'work_pending', 'paused',
    ];

    public function settings(User $user): BreakSetting
    {
        return BreakSetting::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'is_enabled' => true,
                'work_minutes' => 30,
                'break_minutes' => 5,
                'sound_on_break' => true,
                'sound_on_return' => true,
                'visual_alert' => true,
            ],
        );
    }

    public function current(User $user): ?BreakSession
    {
        return BreakSession::query()
            ->with('exercise')
            ->where('user_id', $user->id)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->latest('id')
            ->first();
    }

    /** @return array<string, mixed> */
    public function synchronize(User $user): array
    {
        $settings = $this->settings($user);
        $session = $this->current($user);
        $event = null;

        if ($settings->is_enabled && $session !== null) {
            $now = CarbonImmutable::now();

            if ($session->status === BreakCycleStatus::Working && $session->scheduled_at?->isPast()) {
                $session->fill([
                    'status' => BreakCycleStatus::BreakPending,
                    'notified_at' => $now,
                    'updated_by' => $user->id,
                ]);
                $session->save();
                $this->record($session, $user, 'break_notified');
                $event = $settings->sound_on_break ? 'break-start' : 'break-start-visual';
            }

            if ($session->status === BreakCycleStatus::BreakActive && $session->started_at?->addMinutes($session->configured_break_minutes)->isPast()) {
                $session->fill([
                    'status' => BreakCycleStatus::BreakCompleted,
                    'ended_at' => $now,
                    'actual_duration_seconds' => $session->started_at?->diffInSeconds($now),
                    'updated_by' => $user->id,
                ]);
                $session->save();
                $this->record($session, $user, 'break_finished');
                $event = $settings->sound_on_return ? 'break-finished' : 'break-finished-visual';
            }

            $session = $session->refresh()->load('exercise');
        }

        return $this->snapshot($user, $settings, $session, $event);
    }

    public function startWork(User $user): BreakSession
    {
        $settings = $this->settings($user);
        abort_unless($settings->is_enabled, 422, 'Las pausas activas están desactivadas.');

        $current = $this->current($user);
        if ($current?->status === BreakCycleStatus::Working) {
            return $current;
        }

        $now = CarbonImmutable::now();
        $session = DB::transaction(function () use ($user, $settings, $now, $current): BreakSession {
            if ($current !== null && in_array($current->status, [BreakCycleStatus::WorkPending, BreakCycleStatus::BreakCancelled, BreakCycleStatus::BreakCompleted], true)) {
                $current->update([
                    'returned_to_work_at' => $now,
                    'updated_by' => $user->id,
                ]);
                $this->record($current, $user, 'returned_to_work');
            }

            $session = BreakSession::query()->create([
                'user_id' => $user->id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'scheduled_at' => $now->addMinutes($settings->work_minutes),
                'configured_work_minutes' => $settings->work_minutes,
                'configured_break_minutes' => $settings->break_minutes,
                'status' => BreakCycleStatus::Working,
            ]);
            $this->record($session, $user, 'work_started');

            return $session;
        });

        return $session->refresh();
    }

    public function acceptBreak(User $user): BreakSession
    {
        $session = $this->current($user);
        abort_unless($session?->status === BreakCycleStatus::BreakPending, 422, 'No hay una pausa pendiente.');

        $exercise = BreakExercise::query()->availableTo($user->id)->inRandomOrder()->first();
        $now = CarbonImmutable::now();
        $session->fill([
            'exercise_id' => $exercise?->id,
            'accepted_at' => $now,
            'started_at' => $now,
            'status' => BreakCycleStatus::BreakActive,
            'updated_by' => $user->id,
        ]);
        $session->save();
        $this->record($session, $user, 'break_accepted');

        return $session->refresh()->load('exercise');
    }

    public function cancelBreak(User $user): BreakSession
    {
        $session = $this->current($user);
        abort_unless($session?->status === BreakCycleStatus::BreakPending, 422, 'No hay una pausa pendiente.');

        $session->fill([
            'cancelled_at' => CarbonImmutable::now(),
            'status' => BreakCycleStatus::BreakCancelled,
            'updated_by' => $user->id,
        ]);
        $session->save();
        $this->record($session, $user, 'break_cancelled');

        return $session->refresh();
    }

    public function cancelActiveBreak(User $user): BreakSession
    {
        $session = $this->current($user);
        abort_unless($session?->status === BreakCycleStatus::BreakActive, 422, 'No hay una pausa activa.');

        $now = CarbonImmutable::now();
        $session->fill([
            'cancelled_at' => $now,
            'ended_at' => $now,
            'actual_duration_seconds' => $session->started_at?->diffInSeconds($now),
            'status' => BreakCycleStatus::BreakCancelled,
            'updated_by' => $user->id,
        ]);
        $session->save();
        $this->record($session, $user, 'break_cancelled');

        return $session->refresh();
    }

    public function pauseWork(User $user): BreakSession
    {
        $session = $this->current($user);
        abort_unless($session?->status === BreakCycleStatus::Working, 422, 'No hay un período de trabajo activo.');

        $remainingSeconds = max(0, CarbonImmutable::now()->diffInSeconds($session->scheduled_at, false));
        $session->fill([
            'status' => BreakCycleStatus::Paused,
            'paused_at' => CarbonImmutable::now(),
            'paused_remaining_seconds' => $remainingSeconds,
            'updated_by' => $user->id,
        ]);
        $session->save();
        $this->record($session, $user, 'work_paused');

        return $session->refresh();
    }

    public function resumeWork(User $user): BreakSession
    {
        $session = $this->current($user);
        abort_unless($session?->status === BreakCycleStatus::Paused, 422, 'El contador no está detenido.');

        $now = CarbonImmutable::now();
        $session->fill([
            'status' => BreakCycleStatus::Working,
            'scheduled_at' => $now->addSeconds((int) $session->paused_remaining_seconds),
            'resumed_at' => $now,
            'updated_by' => $user->id,
        ]);
        $session->save();
        $this->record($session, $user, 'work_resumed');

        return $session->refresh();
    }

    /** @return array<string, mixed> */
    public function snapshot(User $user, ?BreakSetting $settings = null, ?BreakSession $session = null, ?string $event = null): array
    {
        $settings ??= $this->settings($user);
        $session ??= $this->current($user);
        $targetAt = null;
        $remaining = 0;

        if ($session?->status === BreakCycleStatus::Working) {
            $targetAt = $session->scheduled_at?->toIso8601String();
        } elseif ($session?->status === BreakCycleStatus::BreakActive) {
            $targetAt = $session->started_at?->addMinutes($session->configured_break_minutes)->toIso8601String();
        }

        if ($targetAt !== null) {
            $remaining = max(0, CarbonImmutable::now()->diffInSeconds(CarbonImmutable::parse($targetAt), false));
        }

        $completedToday = BreakSession::query()->where('user_id', $user->id)->where('status', BreakCycleStatus::BreakCompleted)->whereDate('ended_at', today())->count();
        $cancelledToday = BreakSession::query()->where('user_id', $user->id)->whereNotNull('cancelled_at')->whereDate('cancelled_at', today())->count();
        $restSecondsToday = (int) BreakSession::query()->where('user_id', $user->id)->whereDate('ended_at', today())->sum('actual_duration_seconds');

        return [
            'settings' => $settings,
            'session' => $session,
            'status' => $session?->status?->value ?? 'idle',
            'status_label' => $session?->status?->label() ?? 'Sin iniciar',
            'target_at' => $targetAt,
            'remaining_seconds' => $remaining,
            'event' => $event,
            'event_key' => $event !== null && $session !== null ? $session->id.':'.$event.':'.($session->notified_at?->timestamp ?? $session->ended_at?->timestamp ?? time()) : null,
            'completed_today' => $completedToday,
            'cancelled_today' => $cancelledToday,
            'rest_seconds_today' => $restSecondsToday,
            'custom_sound_url' => $settings->custom_sound_path === null
                ? null
                : url('storage/'.ltrim($settings->custom_sound_path, '/')),
        ];
    }

    private function record(BreakSession $session, User $user, string $action): void
    {
        BreakHistory::query()->create([
            'break_id' => $session->id,
            'user_id' => $user->id,
            'action' => $action,
            'data' => $session->toArray(),
        ]);
    }
}
