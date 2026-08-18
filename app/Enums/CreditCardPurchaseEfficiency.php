<?php

declare(strict_types=1);

namespace App\Enums;

enum CreditCardPurchaseEfficiency: string
{
    case Excellent = 'excellent';
    case Good = 'good';
    case Regular = 'regular';
    case Inconvenient = 'inconvenient';
    case Wait = 'wait';

    public function label(): string
    {
        return match ($this) {
            self::Excellent => 'Excelente para comprar',
            self::Good => 'Buena para comprar',
            self::Regular => 'Regular para comprar',
            self::Inconvenient => 'Poco conveniente',
            self::Wait => 'Esperar al próximo corte',
        };
    }
}
