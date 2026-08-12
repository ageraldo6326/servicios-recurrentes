<?php

namespace App\Enums;

enum CommitmentPaymentStatus: string
{
    case Projected = 'projected';
    case Pending = 'pending';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';
}
