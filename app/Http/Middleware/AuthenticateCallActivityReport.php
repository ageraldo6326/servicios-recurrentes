<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateCallActivityReport
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->isProduction() && ! $request->isSecure()) {
            Log::warning('Call activity report rejected: HTTPS is required.', ['source_ip' => $request->ip()]);

            return new JsonResponse(['ok' => false, 'message' => 'HTTPS is required.'], 400);
        }

        $configuredKey = config('services.call_activity.report_key');
        $providedKey = $request->bearerToken();

        if (! is_string($configuredKey) || $configuredKey === '') {
            Log::error('Call activity endpoint is unavailable because its report key is not configured.');

            return new JsonResponse(['ok' => false, 'message' => 'Service unavailable.'], 503);
        }

        if (! is_string($providedKey) || ! hash_equals($configuredKey, $providedKey)) {
            Log::warning('Call activity report rejected: invalid authorization.', ['source_ip' => $request->ip()]);

            return new JsonResponse(['ok' => false, 'message' => 'Unauthorized.'], 401);
        }

        if (! $request->isJson()) {
            Log::notice('Call activity report rejected: JSON content type is required.', ['source_ip' => $request->ip()]);

            return new JsonResponse(['ok' => false, 'message' => 'Content-Type application/json is required.'], 415);
        }

        return $next($request);
    }
}
