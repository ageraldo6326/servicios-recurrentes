<?php

namespace App\Enums;

enum CommercialQuoteStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Viewed = 'viewed';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Converted = 'converted';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador', self::Sent => 'Enviada', self::Viewed => 'Vista',
            self::Accepted => 'Aceptada', self::Rejected => 'Rechazada', self::Expired => 'Expirada', self::Converted => 'Convertida',
        };
    }
}
