<?php

declare(strict_types=1);

namespace App\Enums;

enum SecurityIpBlockScope: string
{
    case Login = 'login';
    case PasswordReset = 'password_reset';
    case Mfa = 'mfa';
    case AllAuth = 'all_auth';
}
