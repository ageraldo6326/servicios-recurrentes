<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente', self::InProgress => 'En progreso', self::Completed => 'Completada', self::Cancelled => 'Cancelada',
        };
    }
}
