<?php

declare(strict_types=1);

namespace App\Enums;

enum CallMonitoringPlatform: string
{
    case Vicidial = 'vicidial';
    case Issabel = 'issabel';

    public function label(): string
    {
        return match ($this) {
            self::Vicidial => 'VICIdial',
            self::Issabel => 'Issabel',
        };
    }
}
