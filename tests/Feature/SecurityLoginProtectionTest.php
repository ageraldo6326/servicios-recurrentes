<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SecurityEventType;
use App\Enums\SecurityIpBlockScope;
use App\Enums\SecurityIpBlockSource;
use App\Exceptions\SecurityIpBlockedException;
use App\Models\SecurityEvent;
use App\Models\SecurityIpBlock;
use App\Models\User;
use App\Services\Security\LoginProtectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

final class SecurityLoginProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_logins_create_a_temporary_ip_block_without_storing_the_email(): void
    {
        config()->set('security.login.account_attempts', 5);
        config()->set('security.login.ip_attempts', 10);
        config()->set('security.login.window_seconds', 600);
        config()->set('security.login.block_seconds', 900);

        $email = 'operador@example.test';
        $request = $this->requestFromIp('203.0.113.10');
        $protection = app(LoginProtectionService::class);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            try {
                $protection->recordFailedLogin($request, $email);
            } catch (SecurityIpBlockedException $exception) {
                $this->assertSame(5, $attempt);
                $this->assertSame(429, $exception->getStatusCode());
            }
        }

        $block = SecurityIpBlock::query()->sole();
        $failedEvent = SecurityEvent::query()
            ->where('event_type', SecurityEventType::LoginFailed->value)
            ->firstOrFail();

        $this->assertSame('203.0.113.10', $block->ip_address);
        $this->assertSame(SecurityIpBlockScope::AllAuth, $block->scope);
        $this->assertSame(SecurityIpBlockSource::Laravel, $block->source);
        $this->assertNotNull($block->expires_at);
        $this->assertSame(5, SecurityEvent::query()->where('event_type', SecurityEventType::LoginFailed->value)->count());
        $this->assertSame(1, SecurityEvent::query()->where('event_type', SecurityEventType::LoginRateLimited->value)->count());
        $this->assertSame(1, SecurityEvent::query()->where('event_type', SecurityEventType::LoginIpBlocked->value)->count());
        $this->assertArrayHasKey('user_hash', $failedEvent->metadata);
        $this->assertStringStartsWith('sha256:', $failedEvent->metadata['user_hash']);
        $this->assertStringNotContainsString($email, json_encode($failedEvent->metadata, JSON_THROW_ON_ERROR));
    }

    public function test_a_laravel_block_returns_429_before_the_login_page_is_rendered(): void
    {
        $ipAddress = '203.0.113.11';
        SecurityIpBlock::query()->create([
            'ip_address' => $ipAddress,
            'scope' => SecurityIpBlockScope::AllAuth,
            'source' => SecurityIpBlockSource::Laravel,
            'reason' => 'Bloqueo de prueba.',
            'blocked_at' => now(),
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => $ipAddress])
            ->get(route('login'))
            ->assertStatus(429)
            ->assertHeader('Retry-After');
    }

    public function test_a_successful_login_clears_the_account_attempt_counter(): void
    {
        config()->set('security.login.account_attempts', 2);
        config()->set('security.login.ip_attempts', 10);

        $user = User::factory()->create(['email' => 'operador@example.test']);
        $request = $this->requestFromIp('203.0.113.12');
        $protection = app(LoginProtectionService::class);

        $protection->recordFailedLogin($request, $user->email);
        $protection->recordSuccessfulLogin($request, $user, $user->email);
        $protection->recordFailedLogin($request, $user->email);

        $this->assertSame(0, SecurityIpBlock::query()->count());
        $this->assertSame(1, SecurityEvent::query()->where('event_type', SecurityEventType::LoginSuccess->value)->count());
    }

    private function requestFromIp(string $ipAddress): Request
    {
        return Request::create('/login', 'POST', [], [], [], [
            'REMOTE_ADDR' => $ipAddress,
            'HTTP_USER_AGENT' => 'Security test agent',
        ]);
    }
}
