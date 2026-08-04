<?php

namespace App\Enums;

enum ChargeStatus: string
{
    case Pending = 'pending';
    case Partial = 'partial';
    case Paid = 'paid';
    case Overdue = 'overdue';
}
