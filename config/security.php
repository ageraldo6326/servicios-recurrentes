<?php

declare(strict_types=1);

return [
    'login' => [
        'account_attempts' => (int) env('SECURITY_LOGIN_ACCOUNT_ATTEMPTS', 5),
        'ip_attempts' => (int) env('SECURITY_LOGIN_IP_ATTEMPTS', 10),
        'window_seconds' => (int) env('SECURITY_LOGIN_WINDOW_SECONDS', 600),
        'block_seconds' => (int) env('SECURITY_LOGIN_BLOCK_SECONDS', 900),
    ],
];
