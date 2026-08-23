<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Enums\SecurityIpBlockScope;
use App\Enums\SecurityIpBlockSource;
use App\Exceptions\SecurityIpBlockedException;
use App\Models\SecurityIpBlock;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class LoginProtectionService
{
    public function __construct(
        private readonly SecurityEventLogger $eventLogger,
    ) {}

    /** @param array<int, string> $scopes */
    public function ensureIpIsNotBlocked(Request $request, array $scopes = [SecurityIpBlockScope::AllAuth->value]): void
    {
        $ipAddress = $this->eventLogger->clientIp($request);

        if ($ipAddress === null) {
            return;
        }

        $block = $this->activeBlock($ipAddress, $scopes);

        if ($block === null) {
            return;
        }

        throw new SecurityIpBlockedException($this->retryAfter($block));
    }

    public function recordFailedLogin(Request $request, string $email): void
    {
        $this->eventLogger->loginFailed($request, $email);

        $ipAddress = $this->eventLogger->clientIp($request);

        if ($ipAddress === null) {
            return;
        }

        $windowSeconds = max(60, (int) config('security.login.window_seconds', 600));
        $accountAttempts = RateLimiter::hit($this->accountThrottleKey($ipAddress, $email), $windowSeconds);
        $ipAttempts = RateLimiter::hit($this->ipThrottleKey($ipAddress), $windowSeconds);

        if (
            $accountAttempts < max(1, (int) config('security.login.account_attempts', 5))
            && $ipAttempts < max(1, (int) config('security.login.ip_attempts', 10))
        ) {
            return;
        }

        $this->eventLogger->loginRateLimited($request, $email);
        $block = $this->blockIp($ipAddress, $accountAttempts, $ipAttempts);
        $this->eventLogger->loginIpBlocked($request, $block);
        RateLimiter::clear($this->accountThrottleKey($ipAddress, $email));
        RateLimiter::clear($this->ipThrottleKey($ipAddress));

        throw new SecurityIpBlockedException($this->retryAfter($block));
    }

    public function recordSuccessfulLogin(Request $request, User $user, string $email): void
    {
        $ipAddress = $this->eventLogger->clientIp($request);

        if ($ipAddress !== null) {
            RateLimiter::clear($this->accountThrottleKey($ipAddress, $email));
        }

        $this->eventLogger->loginSucceeded($request, $user);
    }

    /** @param array<int, string> $scopes */
    private function activeBlock(string $ipAddress, array $scopes): ?SecurityIpBlock
    {
        $scopes = array_values(array_unique([...$scopes, SecurityIpBlockScope::AllAuth->value]));

        return SecurityIpBlock::query()
            ->active()
            ->where('ip_address', $ipAddress)
            ->whereIn('scope', $scopes)
            ->latest('blocked_at')
            ->first();
    }

    private function blockIp(string $ipAddress, int $accountAttempts, int $ipAttempts): SecurityIpBlock
    {
        $existingBlock = $this->activeBlock($ipAddress, [SecurityIpBlockScope::AllAuth->value]);

        if ($existingBlock !== null) {
            return $existingBlock;
        }

        $blockSeconds = max(60, (int) config('security.login.block_seconds', 900));

        return SecurityIpBlock::query()->create([
            'ip_address' => $ipAddress,
            'scope' => SecurityIpBlockScope::AllAuth,
            'source' => SecurityIpBlockSource::Laravel,
            'reason' => 'Límite de intentos de inicio de sesión superado.',
            'blocked_at' => now(),
            'expires_at' => now()->addSeconds($blockSeconds),
            'metadata' => [
                'account_attempts' => $accountAttempts,
                'ip_attempts' => $ipAttempts,
            ],
        ]);
    }

    private function retryAfter(SecurityIpBlock $block): int
    {
        return $block->expires_at === null
            ? max(60, (int) config('security.login.block_seconds', 900))
            : max(1, (int) now()->diffInSeconds($block->expires_at, false));
    }

    private function accountThrottleKey(string $ipAddress, string $email): string
    {
        return 'security:login:account:'.hash('sha256', $ipAddress.'|'.Str::lower(trim($email)));
    }

    private function ipThrottleKey(string $ipAddress): string
    {
        return 'security:login:ip:'.hash('sha256', $ipAddress);
    }
}
