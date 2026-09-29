<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CallActivityState;
use App\Enums\ContractedServiceStatus;
use App\Livewire\ServerCallActivity\Index;
use App\Models\CatalogService;
use App\Models\Client;
use App\Models\ContractedService;
use App\Models\Provider;
use App\Models\ServerCallActivity;
use App\Models\ServerCallActivityReport;
use App\Models\User;
use App\Services\CallActivity\CallActivityStateResolver;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Tests\TestCase;

final class ServerCallActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.call_activity.report_key', 'test-call-activity-secret');
        config()->set('services.call_activity.max_future_minutes', 5);
    }

    public function test_valid_report_creates_activity_and_history_in_utc(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-29T15:02:11Z'));
        Carbon::setTestNow(Carbon::parse('2026-09-29T15:02:11Z'));
        $service = $this->createMonitoredService('203.0.113.10');

        $this->withToken('test-call-activity-secret')
            ->postJson('/api/v1/server-call-activity', [
                'server_ip' => '203.0.113.10',
                'last_outbound_at' => '2026-09-29T10:30:00-04:00',
            ])
            ->assertOk()
            ->assertExactJson([
                'ok' => true,
                'server_id' => $service->id,
                'last_outbound_at' => '2026-09-29T14:30:00Z',
                'last_reported_at' => '2026-09-29T15:02:11Z',
                'updated_last_outbound' => true,
            ]);

        $this->assertDatabaseHas('server_call_activities', [
            'contracted_service_id' => $service->id,
            'last_outbound_at' => '2026-09-29 14:30:00',
            'last_reported_at' => '2026-09-29 15:02:11',
        ]);
        $this->assertDatabaseHas('server_call_activity_reports', [
            'contracted_service_id' => $service->id,
            'updated_last_outbound' => false + 1,
        ]);

        CarbonImmutable::setTestNow();
        Carbon::setTestNow();
    }

    public function test_same_older_and_null_reports_never_move_or_clear_last_outbound(): void
    {
        $service = $this->createMonitoredService('203.0.113.20');
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-29T15:00:00Z'));
        Carbon::setTestNow(Carbon::parse('2026-09-29T15:00:00Z'));

        $this->report('203.0.113.20', '2026-09-29T14:30:00Z')->assertOk();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-29T16:00:00Z'));
        Carbon::setTestNow(Carbon::parse('2026-09-29T16:00:00Z'));
        $this->report('203.0.113.20', '2026-09-29T14:30:00Z')
            ->assertOk()
            ->assertJsonPath('updated_last_outbound', false);
        $this->report('203.0.113.20', '2026-09-28T14:30:00Z')
            ->assertOk()
            ->assertJsonPath('updated_last_outbound', false);
        $this->report('203.0.113.20', null)
            ->assertOk()
            ->assertJsonPath('updated_last_outbound', false);

        $activity = $service->callActivity()->firstOrFail();
        $this->assertSame('2026-09-29T14:30:00+00:00', $activity->last_outbound_at->toIso8601String());
        $this->assertSame('2026-09-29T16:00:00+00:00', $activity->last_reported_at->toIso8601String());
        $this->assertSame(4, $service->callActivityReports()->count());

        CarbonImmutable::setTestNow();
        Carbon::setTestNow();
    }

    public function test_null_report_initializes_server_without_calls(): void
    {
        $service = $this->createMonitoredService('2001:db8::10');

        $this->report('2001:0db8:0:0:0:0:0:10', null)
            ->assertOk()
            ->assertJsonPath('last_outbound_at', null)
            ->assertJsonPath('updated_last_outbound', false);

        $this->assertDatabaseHas('server_call_activities', [
            'contracted_service_id' => $service->id,
            'last_outbound_at' => null,
        ]);
    }

    public function test_unauthorized_unknown_ambiguous_and_invalid_reports_do_not_change_activity(): void
    {
        $known = $this->createMonitoredService('203.0.113.30');

        $this->postJson('/api/v1/server-call-activity', [
            'server_ip' => '203.0.113.30',
            'last_outbound_at' => null,
        ])->assertUnauthorized();

        $this->report('203.0.113.99', null)->assertNotFound();
        $this->report('203.0.113.30', '2026-09-29 14:30:00')->assertUnprocessable();
        $this->report('203.0.113.30', '2026-02-30T14:30:00Z')->assertUnprocessable();
        $this->withToken('test-call-activity-secret')->postJson('/api/v1/server-call-activity', [
            'server_ip' => '203.0.113.30',
            'last_outbound_at' => null,
            'unexpected' => true,
        ])->assertUnprocessable();

        $this->createMonitoredService('203.0.113.30');
        $this->report('203.0.113.30', null)->assertStatus(409);

        $this->assertDatabaseCount('server_call_activities', 0);
        $this->assertDatabaseCount('server_call_activity_reports', 0);
        $this->assertFalse($known->callActivity()->exists());
    }

    public function test_future_report_beyond_clock_tolerance_is_rejected(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-29T15:00:00Z'));
        Carbon::setTestNow(Carbon::parse('2026-09-29T15:00:00Z'));
        $this->createMonitoredService('203.0.113.40');

        $this->report('203.0.113.40', '2026-09-29T15:05:01Z')->assertUnprocessable();
        $this->assertDatabaseCount('server_call_activities', 0);

        CarbonImmutable::setTestNow();
        Carbon::setTestNow();
    }

    public function test_api_requires_json_content_type(): void
    {
        $this->createMonitoredService('203.0.113.45');

        $this->withToken('test-call-activity-secret')
            ->post('/api/v1/server-call-activity', [
                'server_ip' => '203.0.113.45',
                'last_outbound_at' => null,
            ])
            ->assertStatus(415);

        $this->assertDatabaseCount('server_call_activities', 0);
    }

    public function test_disabled_or_cancelled_services_are_not_resolved_by_the_api(): void
    {
        $disabled = $this->createMonitoredService('203.0.113.50');
        $disabled->update(['call_monitoring_enabled' => false]);
        $cancelled = $this->createMonitoredService('203.0.113.51');
        $cancelled->update(['status' => ContractedServiceStatus::Cancelled]);

        $this->report('203.0.113.50', null)->assertNotFound();
        $this->report('203.0.113.51', null)->assertNotFound();
    }

    public function test_panel_prioritizes_missing_reports_and_exposes_filters_and_history(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-29T15:00:00Z'));
        Carbon::setTestNow(Carbon::parse('2026-09-29T15:00:00Z'));
        $user = User::factory()->create();
        $service = $this->createMonitoredService('203.0.113.60', 'Cliente sin reporte');
        ServerCallActivity::create([
            'contracted_service_id' => $service->id,
            'last_outbound_at' => now('UTC')->subHours(100),
            'last_reported_at' => now('UTC')->subHours(40),
        ]);
        ServerCallActivityReport::create([
            'contracted_service_id' => $service->id,
            'received_at' => now('UTC')->subHours(40),
            'reported_last_outbound_at' => now('UTC')->subHours(100),
            'updated_last_outbound' => true,
            'source_ip' => '198.51.100.8',
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->assertSee('Cliente sin reporte')
            ->assertSee('Sin reporte')
            ->assertSee('Tiempo sin marcar')
            ->assertSee('4 días 4 h')
            ->assertSee('Actualizar datos')
            ->assertDontSee('Actualización en vivo')
            ->set('state', 'no_report')
            ->assertSee('Cliente sin reporte')
            ->call('openHistory', $service->id)
            ->assertSee('198.51.100.8')
            ->assertSee('Marcado actualizado');

        CarbonImmutable::setTestNow();
        Carbon::setTestNow();
    }

    public function test_state_resolver_distinguishes_every_monitoring_condition(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-29T15:00:00Z'));
        $resolver = app(CallActivityStateResolver::class);

        $noData = $this->createMonitoredService('203.0.113.81', 'Sin datos');
        $noCalls = $this->createMonitoredService('203.0.113.82', 'Sin llamadas');
        $inactive = $this->createMonitoredService('203.0.113.83', 'Inactivo');
        $active = $this->createMonitoredService('203.0.113.84', 'Activo');

        ServerCallActivity::create(['contracted_service_id' => $noCalls->id, 'last_reported_at' => '2026-09-29 14:00:00']);
        ServerCallActivity::create(['contracted_service_id' => $inactive->id, 'last_reported_at' => '2026-09-29 14:00:00', 'last_outbound_at' => '2026-09-26 14:00:00']);
        ServerCallActivity::create(['contracted_service_id' => $active->id, 'last_reported_at' => '2026-09-29 14:00:00', 'last_outbound_at' => '2026-09-29 13:00:00']);

        $this->assertSame(CallActivityState::NoData, $resolver->resolve($noData->load('callActivity')));
        $this->assertSame(CallActivityState::NoCalls, $resolver->resolve($noCalls->load('callActivity')));
        $this->assertSame(CallActivityState::Inactive, $resolver->resolve($inactive->load('callActivity')));
        $this->assertSame(CallActivityState::Active, $resolver->resolve($active->load('callActivity')));

        CarbonImmutable::setTestNow();
    }

    public function test_panel_recalculates_elapsed_time_when_refreshed_after_a_new_outbound_call(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-29T15:00:00Z'));
        Carbon::setTestNow(Carbon::parse('2026-09-29T15:00:00Z'));
        $service = $this->createMonitoredService('203.0.113.85', 'Cliente con actividad nueva');
        $activity = ServerCallActivity::create([
            'contracted_service_id' => $service->id,
            'last_outbound_at' => '2026-09-29 13:00:00',
            'last_reported_at' => '2026-09-29 14:59:00',
        ]);

        $component = Livewire::actingAs(User::factory()->create())
            ->test(Index::class)
            ->assertSee('2 h 0 min')
            ->assertSee('Actualizar datos')
            ->assertDontSee('wire:poll', false)
            ->assertDontSee('callActivityElapsed', false);

        $activity->update([
            'last_outbound_at' => '2026-09-29 14:55:00',
            'last_reported_at' => '2026-09-29 15:00:00',
        ]);

        $component->call('$refresh')
            ->assertSee('5 min 0 s')
            ->assertDontSee('2 h 0 min');

        CarbonImmutable::setTestNow();
        Carbon::setTestNow();
    }

    public function test_panel_alerts_only_after_exceeding_five_days_without_outbound_calls(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-29T15:00:00Z'));
        Carbon::setTestNow(Carbon::parse('2026-09-29T15:00:00Z'));
        config()->set('services.call_activity.no_usage_alert_hours', 120);

        $atBoundary = $this->createMonitoredService('203.0.113.86', 'Cliente justo en el límite');
        ServerCallActivity::create([
            'contracted_service_id' => $atBoundary->id,
            'last_outbound_at' => '2026-09-24 15:00:00',
            'last_reported_at' => '2026-09-29 14:59:00',
        ]);

        $overBoundary = $this->createMonitoredService('203.0.113.87', 'Cliente con alerta');
        ServerCallActivity::create([
            'contracted_service_id' => $overBoundary->id,
            'last_outbound_at' => '2026-09-24 14:00:00',
            'last_reported_at' => '2026-09-29 14:59:00',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(Index::class)
            ->assertSee('1 servidor supera 120 horas (5 días) sin llamadas salientes.')
            ->assertSee('Cliente justo en el límite')
            ->assertSee('5 días 0 h')
            ->assertSee('Cliente con alerta')
            ->assertSee('5 días 1 h')
            ->assertSee('Alerta: superó 120 horas (5 días) sin uso');

        CarbonImmutable::setTestNow();
        Carbon::setTestNow();
    }

    public function test_service_form_requires_unique_ip_only_when_monitoring_is_enabled(): void
    {
        $user = User::factory()->create();
        $existing = $this->createMonitoredService('203.0.113.90');
        $candidate = $this->createMonitoredService('203.0.113.91');
        $candidate->update(['call_monitoring_enabled' => false, 'ip' => 'legacy-hostname.local']);

        $payload = [
            'client_id' => $candidate->client_id,
            'catalog_service_id' => $candidate->catalog_service_id,
            'provider_id' => $candidate->provider_id,
            'price' => 100,
            'price_currency' => 'USD',
            'cost' => 50,
            'cost_currency' => 'USD',
            'ip' => $existing->ip,
            'call_monitoring_platform' => 'issabel',
            'call_monitoring_enabled' => '1',
            'inactivity_threshold_hours' => 72,
            'report_delay_threshold_hours' => 24,
            'billing_day' => 15,
            'starts_at' => '2026-01-01',
        ];

        $this->actingAs($user)
            ->put(route('contracted-services.update', $candidate), $payload)
            ->assertSessionHasErrors('ip');

        $this->actingAs($user)
            ->put(route('contracted-services.update', $candidate), [...$payload, 'call_monitoring_enabled' => '0', 'ip' => 'legacy-hostname.local'])
            ->assertRedirect(route('contracted-services.index'));

        $this->assertDatabaseHas('contracted_services', [
            'id' => $candidate->id,
            'ip' => 'legacy-hostname.local',
            'call_monitoring_enabled' => false,
        ]);
    }

    public function test_authenticated_user_can_open_panel_and_guest_cannot(): void
    {
        $this->get(route('server-call-activity.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())
            ->get(route('server-call-activity.index'))
            ->assertOk()
            ->assertSee('Actividad de marcado');
    }

    public function test_report_history_pruning_honors_configured_retention(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-29T15:00:00Z'));
        Carbon::setTestNow(Carbon::parse('2026-09-29T15:00:00Z'));
        config()->set('services.call_activity.report_retention_days', 90);
        $service = $this->createMonitoredService('203.0.113.70');
        ServerCallActivityReport::create(['contracted_service_id' => $service->id, 'received_at' => now('UTC')->subDays(91)]);
        ServerCallActivityReport::create(['contracted_service_id' => $service->id, 'received_at' => now('UTC')->subDays(89)]);

        $this->artisan('call-activity:prune-reports')->assertSuccessful();

        $this->assertSame(1, ServerCallActivityReport::query()->count());
        CarbonImmutable::setTestNow();
        Carbon::setTestNow();
    }

    private function report(string $serverIp, ?string $lastOutboundAt): TestResponse
    {
        return $this->withToken('test-call-activity-secret')->postJson('/api/v1/server-call-activity', [
            'server_ip' => $serverIp,
            'last_outbound_at' => $lastOutboundAt,
        ]);
    }

    private function createMonitoredService(string $ip, string $clientName = 'Cliente monitoreado'): ContractedService
    {
        $client = Client::create(['name' => $clientName, 'phone' => fake()->numerify('809#######')]);
        $catalogService = CatalogService::create(['name' => 'PBX '.fake()->unique()->numerify('###'), 'is_active' => true]);
        $provider = Provider::create(['name' => 'Proveedor '.fake()->unique()->numerify('###'), 'payment_method' => 'Mensual']);

        return ContractedService::create([
            'client_id' => $client->id,
            'catalog_service_id' => $catalogService->id,
            'provider_id' => $provider->id,
            'price' => 100,
            'price_currency' => 'USD',
            'cost' => 50,
            'cost_currency' => 'USD',
            'ip' => $ip,
            'call_monitoring_platform' => 'vicidial',
            'call_monitoring_enabled' => true,
            'inactivity_threshold_hours' => 48,
            'report_delay_threshold_hours' => 36,
            'billing_day' => 15,
            'status' => ContractedServiceStatus::Active,
            'starts_at' => '2026-01-01',
        ]);
    }
}
