<?php

namespace App\Enums;

enum CommitmentPaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
}
