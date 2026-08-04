<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Validated = 'validated';
    case Rejected = 'rejected';
}
