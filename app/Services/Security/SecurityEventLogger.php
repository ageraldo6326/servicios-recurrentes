<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Enums\SecurityEventSeverity;
use App\Enums\SecurityEventType;
use App\Models\SecurityEvent;
use App\Models\SecurityIpBlock;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class SecurityEventLogger
{
    public function loginFailed(Request $request, string $email): SecurityEvent
    {
        $emailHash = $this->emailHash($email);

        return $this->record(
            SecurityEventType::LoginFailed,
            SecurityEventSeverity::Warning,
            $request,
            null,
            ['user_hash' => $emailHash],
            ['user_hash' => $emailHash],
            'login',
        );
    }

    public function loginRateLimited(Request $request, string $email): SecurityEvent
    {
        $emailHash = $this->emailHash($email);

        return $this->record(
            SecurityEventType::LoginRateLimited,
            SecurityEventSeverity::Warning,
            $request,
            null,
            ['user_hash' => $emailHash],
            ['user_hash' => $emailHash],
            'login',
        );
    }

    public function loginIpBlocked(Request $request, SecurityIpBlock $block): SecurityEvent
    {
        $duration = $block->expires_at === null ? 0 : max(0, now()->diffInSeconds($block->expires_at, false));

        return $this->record(
            SecurityEventType::LoginIpBlocked,
            SecurityEventSeverity::Critical,
            $request,
            null,
            ['source' => $block->source->value, 'duration' => $duration],
            ['source' => $block->source->value, 'duration' => $duration],
            'login',
        );
    }

    public function loginSucceeded(Request $request, User $user): SecurityEvent
    {
        return $this->record(
            SecurityEventType::LoginSuccess,
            SecurityEventSeverity::Info,
            $request,
            $user,
            [],
            [],
            'login',
        );
    }

    public function clientIp(Request $request): ?string
    {
        $ipAddress = $request->ip();

        return filter_var($ipAddress, FILTER_VALIDATE_IP) === false ? null : $ipAddress;
    }

    private function record(
        SecurityEventType $eventType,
        SecurityEventSeverity $severity,
        Request $request,
        ?User $user,
        array $metadata,
        array $logFields,
        ?string $route = null,
    ): SecurityEvent {
        $ipAddress = $this->clientIp($request);
        $route ??= $request->route()?->getName() ?? $request->path();

        $event = SecurityEvent::query()->create([
            'event_type' => $eventType,
            'severity' => $severity,
            'user_id' => $user?->id,
            'ip_address' => $ipAddress,
            'route' => Str::limit($route, 160, ''),
            'method' => Str::upper($request->method()),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'metadata' => $metadata === [] ? null : $metadata,
            'occurred_at' => now(),
        ]);

        $this->writeFail2banLog($eventType, $severity, $ipAddress, $route, $logFields);

        return $event;
    }

    private function writeFail2banLog(
        SecurityEventType $eventType,
        SecurityEventSeverity $severity,
        ?string $ipAddress,
        string $route,
        array $fields,
    ): void {
        $parts = [
            'SECURITY_'.$eventType->value,
            'ip='.($ipAddress ?? 'unknown'),
            'route='.$this->logValue($route),
        ];

        foreach ($fields as $key => $value) {
            $parts[] = $this->logValue((string) $key).'='.$this->logValue((string) $value);
        }

        Log::channel('security')->log($severity->value, implode(' ', $parts));
    }

    private function emailHash(string $email): string
    {
        return 'sha256:'.hash_hmac('sha256', Str::lower(trim($email)), (string) config('app.key'));
    }

    private function logValue(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9._:-]/', '_', $value) ?: 'unknown';
    }
}
