<?php

namespace App\Enums;

enum BreakCycleStatus: string
{
    case Working = 'working';
    case BreakPending = 'break_pending';
    case BreakCancelled = 'break_cancelled';
    case BreakActive = 'break_active';
    case BreakCompleted = 'break_completed';
    case WorkPending = 'work_pending';
    case Paused = 'paused';

    public function label(): string
    {
        return match ($this) {
            self::Working => 'Trabajando',
            self::BreakPending => 'Pausa pendiente',
            self::BreakCancelled => 'Pausa omitida',
            self::BreakActive => 'Pausa activa',
            self::BreakCompleted => 'Pausa finalizada',
            self::WorkPending => 'Listo para trabajar',
            self::Paused => 'Contador detenido',
        };
    }
}
