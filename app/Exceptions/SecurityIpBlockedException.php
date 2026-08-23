<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

final class SecurityIpBlockedException extends HttpException
{
    public function __construct(int $retryAfter)
    {
        parent::__construct(
            429,
            'Demasiados intentos. Intenta nuevamente cuando finalice el tiempo de bloqueo.',
            null,
            ['Retry-After' => max(1, $retryAfter)],
        );
    }
}
