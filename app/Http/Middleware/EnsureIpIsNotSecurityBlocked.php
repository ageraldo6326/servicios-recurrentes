<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Security\LoginProtectionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureIpIsNotSecurityBlocked
{
    public function __construct(
        private readonly LoginProtectionService $loginProtection,
    ) {}

    public function handle(Request $request, Closure $next, string ...$scopes): Response
    {
        $this->loginProtection->ensureIpIsNotBlocked($request, $scopes);

        return $next($request);
    }
}
