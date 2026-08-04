<?php

namespace App\Enums;

enum CommercialInvoiceStatus: string
{
    case Draft = 'draft'; case Pending = 'pending'; case Paid = 'paid'; case Partial = 'partial'; case Overdue = 'overdue'; case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador', self::Pending => 'Pendiente', self::Paid => 'Pagada', self::Partial => 'Parcial', self::Overdue => 'Vencida', self::Cancelled => 'Anulada',
        };
    }
}
