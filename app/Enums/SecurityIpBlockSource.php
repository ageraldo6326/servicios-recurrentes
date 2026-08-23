<?php

declare(strict_types=1);

namespace App\Enums;

enum SecurityIpBlockSource: string
{
    case Laravel = 'laravel';
    case Manual = 'manual';
}
