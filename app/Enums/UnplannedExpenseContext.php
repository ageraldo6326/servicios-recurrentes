<?php

namespace App\Enums;

enum UnplannedExpenseContext: string
{
    case Personal = 'personal';
    case Business = 'business';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::Personal => 'Personal',
            self::Business => 'Negocio',
            self::Both => 'Ambos',
        };
    }
}
