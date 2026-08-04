<?php

namespace App\Enums;

enum FinancialCommitmentPriority: string
{
    case Critical = 'critical';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';
    case Informational = 'informational';
    case Future = 'future';
    case Paid = 'paid';

    public function rank(): int
    {
        return match ($this) {
            self::Critical => 1,
            self::High => 2,
            self::Medium => 3,
            self::Low => 4,
            self::Informational => 5,
            self::Future => 6,
            self::Paid => 7,
        };
    }
}
