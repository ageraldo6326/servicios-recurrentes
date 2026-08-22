<?php

namespace Tests\Feature;

use App\Enums\ContractedServiceStatus;
use App\Enums\ChargeStatus;
use App\Models\Charge;
use App\Models\CatalogService;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\ContractedService;
use App\Models\Gestion;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Tests\TestCase;

class ContractedServiceModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_it_cannot_contract_an_inactive_catalog_service(): void
    {
        $catalogService = CatalogService::create(['name' => 'PBX', 'is_active' => false]);
        $client = Client::create(['name' => 'Cliente', 'phone' => '8090000000']);
        $provider = Provider::create(['name' => 'Proveedor', 'payment_method' => 'Mensual']);

        $response = $this->post(route('contracted-services.store'), $this->payload($client, $catalogService, $provider));

        $response->assertStatus(422);
        $this->assertDatabaseCount('contracted_services', 0);
    }

    public function test_changing_any_field_updates_the_same_service(): void
    {
        [$client, $catalogService, $provider] = $this->entities();
        $service = ContractedService::create([...$this->payload($client, $catalogService, $provider), 'status' => ContractedServiceStatus::Active]);

        $response = $this->put(route('contracted-services.update', $service), [...$this->payload($client, $catalogService, $provider), 'price' => 75]);

        $response->assertRedirect(route('contracted-services.index'));
        $this->assertDatabaseCount('contracted_services', 1);
        $this->assertDatabaseHas('contracted_services', ['id' => $service->id, 'price' => 75, 'status' => 'active']);
    }

    public function test_saving_without_commercial_changes_updates_the_same_service(): void
    {
        [$client, $catalogService, $provider] = $this->entities();
        $service = ContractedService::create([...$this->payload($client, $catalogService, $provider), 'status' => ContractedServiceStatus::Active]);

        $this->put(route('contracted-services.update', $service), [...$this->payload($client, $catalogService, $provider), 'ip' => '192.0.2.10']);

        $this->assertDatabaseCount('contracted_services', 1);
        $this->assertDatabaseHas('contracted_services', ['id' => $service->id, 'ip' => '192.0.2.10', 'status' => 'active']);
    }

    public function test_correcting_the_client_updates_the_same_active_service(): void
    {
        [$client, $catalogService, $provider] = $this->entities();
        $newClient = Client::create(['name' => 'Cliente corregido', 'phone' => '8091111111']);
        $service = ContractedService::create([...$this->payload($client, $catalogService, $provider), 'status' => ContractedServiceStatus::Active]);

        $this->put(route('contracted-services.update', $service), [...$this->payload($newClient, $catalogService, $provider), 'ip' => '192.0.2.11']);

        $this->assertDatabaseCount('contracted_services', 1);
        $this->assertDatabaseHas('contracted_services', [
            'id' => $service->id,
            'client_id' => $newClient->id,
            'status' => 'active',
            'ip' => '192.0.2.11',
        ]);
    }

    public function test_cancelling_a_service_persists_the_reason(): void
    {
        [$client, $catalogService, $provider] = $this->entities();
        $service = ContractedService::create([...$this->payload($client, $catalogService, $provider), 'status' => ContractedServiceStatus::Active]);

        $this->post(route('contracted-services.cancel', $service), ['cancellation_reason' => 'Cliente no continuará']);

        $this->assertDatabaseHas('contracted_services', [
            'id' => $service->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Cliente no continuará',
        ]);
    }

    public function test_a_service_without_history_can_be_deleted(): void
    {
        [$client, $catalogService, $provider] = $this->entities();
        $service = ContractedService::create([...$this->payload($client, $catalogService, $provider), 'status' => ContractedServiceStatus::Active]);

        $this->delete(route('contracted-services.destroy', $service))
            ->assertRedirect(route('contracted-services.index'));

        $this->assertDatabaseMissing('contracted_services', ['id' => $service->id]);
    }

    public function test_a_service_with_history_cannot_be_deleted(): void
    {
        [$client, $catalogService, $provider] = $this->entities();
        $service = ContractedService::create([...$this->payload($client, $catalogService, $provider), 'status' => ContractedServiceStatus::Active]);
        Charge::create(['contracted_service_id' => $service->id, 'status' => ChargeStatus::Pending, 'amount' => 50, 'currency' => 'USD', 'due_date' => now()->toDateString()]);

        $this->delete(route('contracted-services.destroy', $service))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('contracted_services', ['id' => $service->id]);
    }

    public function test_executive_dashboard_projects_active_services_without_manual_charges(): void
    {
        [$client, $catalogService, $provider] = $this->entities();
        ContractedService::create([
            ...$this->payload($client, $catalogService, $provider),
            'price' => 50,
            'cost' => 20,
            'starts_at' => '2026-06-15',
            'status' => ContractedServiceStatus::Active,
        ]);

        $response = $this->get(route('dashboard.executive', ['from' => '2026-07-01', 'to' => '2026-07-31']));

        $response->assertOk()->assertViewHas('projectedIncome', 50.0)->assertViewHas('projectedCosts', 20.0);
    }

    public function test_follow_up_dashboard_lists_services_with_overdue_charges(): void
    {
        [$client, $catalogService, $provider] = $this->entities();
        $service = ContractedService::create([...$this->payload($client, $catalogService, $provider), 'status' => ContractedServiceStatus::Active]);
        Charge::create([
            'contracted_service_id' => $service->id,
            'status' => ChargeStatus::Overdue,
            'amount' => 50,
            'currency' => 'USD',
            'due_date' => now()->subDay()->toDateString(),
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('¿Qué debo gestionar hoy?')
            ->assertSee('Cobro vencido')
            ->assertSee($client->name);
    }

    public function test_follow_up_dashboard_uses_the_contracted_service_billing_day_without_manual_charge(): void
    {
        [$client, $catalogService, $provider] = $this->entities();
        ContractedService::create([
            ...$this->payload($client, $catalogService, $provider),
            'billing_day' => now()->day,
            'status' => ContractedServiceStatus::Active,
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Cobro de hoy')
            ->assertSee('Día '.now()->day);
    }

    public function test_follow_up_uses_the_configured_company_timezone_for_day_boundaries(): void
    {
        [$client, $catalogService, $provider] = $this->entities();
        ContractedService::create([
            ...$this->payload($client, $catalogService, $provider),
            'billing_day' => 12,
            'status' => ContractedServiceStatus::Active,
        ]);
        CompanySetting::create(['timezone' => 'America/Santo_Domingo']);
        Carbon::setTestNow(Carbon::parse('2026-08-12 00:30:00', 'UTC'));

        $this->get(route('dashboard'))->assertSee('Próximo vencimiento');

        CompanySetting::query()->update(['timezone' => 'UTC']);

        $this->get(route('dashboard'))->assertSee('Cobro de hoy');
        Carbon::setTestNow();
    }

    public function test_follow_up_uses_the_configured_upcoming_due_days(): void
    {
        [$client, $catalogService, $provider] = $this->entities();
        ContractedService::create([
            ...$this->payload($client, $catalogService, $provider),
            'billing_day' => 8,
            'status' => ContractedServiceStatus::Active,
        ]);
        CompanySetting::create([
            'timezone' => 'America/Santo_Domingo',
            'upcoming_due_days' => 7,
        ]);
        Carbon::setTestNow(Carbon::parse('2026-08-01 12:00:00', 'America/Santo_Domingo'));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Próximo vencimiento')
            ->assertSee($client->name);

        CompanySetting::query()->update(['upcoming_due_days' => 6]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee($client->name);
        Carbon::setTestNow();
    }

    public function test_marking_a_service_as_paid_registers_payment_and_automatic_gestion(): void
    {
        [$client, $catalogService, $provider] = $this->entities();
        $service = ContractedService::create([
            ...$this->payload($client, $catalogService, $provider),
            'billing_day' => now()->day,
            'status' => ContractedServiceStatus::Active,
        ]);

        $this->post(route('contracted-services.mark-paid', $service))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('charges', ['contracted_service_id' => $service->id, 'status' => 'paid']);
        $this->assertDatabaseHas('payments', ['amount' => 50, 'status' => 'validated']);
        $this->assertDatabaseHas('gestions', [
            'contracted_service_id' => $service->id,
            'type' => 'Pago recibido',
            'result' => 'El cliente envió el pago sin contacto previo.',
        ]);
        $this->assertInstanceOf(Payment::class, Payment::first());
        $this->assertInstanceOf(Gestion::class, Gestion::first());
    }

    private function entities(): array
    {
        return [
            Client::create(['name' => 'Cliente', 'phone' => '8090000000']),
            CatalogService::create(['name' => 'PBX', 'is_active' => true]),
            Provider::create(['name' => 'Proveedor', 'payment_method' => 'Mensual']),
        ];
    }

    private function payload(Client $client, CatalogService $catalogService, Provider $provider): array
    {
        return [
            'client_id' => $client->id,
            'catalog_service_id' => $catalogService->id,
            'provider_id' => $provider->id,
            'price' => 50,
            'price_currency' => 'USD',
            'cost' => 20,
            'cost_currency' => 'USD',
            'billing_day' => 15,
            'starts_at' => '2026-07-01',
        ];
    }
}
