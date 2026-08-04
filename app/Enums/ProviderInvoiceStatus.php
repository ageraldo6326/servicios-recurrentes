<?php

namespace App\Enums;

enum ProviderInvoiceStatus: string
{
    case Pending = 'pending';
    case Partial = 'partial';
    case Paid = 'paid';
}
