<?php

namespace Tests\Feature;

use App\Enums\ChargeStatus;
use App\Enums\ContractedServiceStatus;
use App\Livewire\ContractedServices\Index as ContractedServicesIndex;
use App\Livewire\Dashboard\FollowUp;
use App\Models\CatalogService;
use App\Models\Charge;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\ContractedService;
use App\Models\Gestion;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_follow_up_dashboard_searches_text_contained_in_service_description(): void
    {
        [$matchingClient, $matchingCatalogService, $matchingProvider] = $this->entities();
        $matchingClient->update(['name' => 'Cliente descripción encontrada']);
        $matchingService = ContractedService::create([
            ...$this->payload($matchingClient, $matchingCatalogService, $matchingProvider),
            'observations' => 'Servidor dedicado con referencia ALFA-900 para la sede central.',
            'status' => ContractedServiceStatus::Active,
        ]);
        Charge::create([
            'contracted_service_id' => $matchingService->id,
            'status' => ChargeStatus::Overdue,
            'amount' => 50,
            'currency' => 'USD',
            'due_date' => now()->subDay()->toDateString(),
        ]);

        $otherClient = Client::create(['name' => 'Cliente sin coincidencia', 'phone' => '8091111111']);
        $otherCatalogService = CatalogService::create(['name' => 'VPS', 'is_active' => true]);
        $otherProvider = Provider::create(['name' => 'Proveedor alterno', 'payment_method' => 'Mensual']);
        $otherService = ContractedService::create([
            ...$this->payload($otherClient, $otherCatalogService, $otherProvider),
            'observations' => 'Instancia de respaldo sin la referencia buscada.',
            'status' => ContractedServiceStatus::Active,
        ]);
        Charge::create([
            'contracted_service_id' => $otherService->id,
            'status' => ChargeStatus::Overdue,
            'amount' => 50,
            'currency' => 'USD',
            'due_date' => now()->subDay()->toDateString(),
        ]);

        Livewire::actingAs(auth()->user())
            ->test(FollowUp::class)
            ->set('search', 'alfa-900')
            ->assertSee('Cliente descripción encontrada')
            ->assertDontSee('Cliente sin coincidencia');
    }

    public function test_contracted_services_searches_text_contained_in_service_description(): void
    {
        [$matchingClient, $matchingCatalogService, $matchingProvider] = $this->entities();
        $matchingClient->update(['name' => 'Cliente servicio encontrado']);
        ContractedService::create([
            ...$this->payload($matchingClient, $matchingCatalogService, $matchingProvider),
            'observations' => 'Servidor dedicado con referencia ALFA-900 para la sede central.',
            'status' => ContractedServiceStatus::Active,
        ]);

        $otherClient = Client::create(['name' => 'Cliente servicio no encontrado', 'phone' => '8092222222']);
        $otherCatalogService = CatalogService::create(['name' => 'VPS', 'is_active' => true]);
        $otherProvider = Provider::create(['name' => 'Proveedor alterno', 'payment_method' => 'Mensual']);
        ContractedService::create([
            ...$this->payload($otherClient, $otherCatalogService, $otherProvider),
            'observations' => 'Instancia de respaldo sin la referencia buscada.',
            'status' => ContractedServiceStatus::Active,
        ]);

        Livewire::actingAs(auth()->user())
            ->test(ContractedServicesIndex::class)
            ->set('search', 'alfa-900')
            ->assertSee('Descripción')
            ->assertSee('Cliente servicio encontrado')
            ->assertSee('Servidor dedicado con referencia ALFA-900 para la sede central.')
            ->assertDontSee('Cliente servicio no encontrado');
    }

    public function test_follow_up_dashboard_always_lists_an_unpaid_charge_from_a_previous_period(): void
    {
        [$client, $catalogService, $provider] = $this->entities();
        $client->update(['name' => 'Cliente con cobro histórico pendiente']);
        $service = ContractedService::create([
            ...$this->payload($client, $catalogService, $provider),
            'billing_day' => 15,
            'status' => ContractedServiceStatus::Active,
        ]);
        CompanySetting::create([
            'timezone' => 'America/Santo_Domingo',
            'upcoming_due_days' => 1,
        ]);
        Charge::create([
            'contracted_service_id' => $service->id,
            'status' => ChargeStatus::Pending,
            'amount' => 50,
            'currency' => 'USD',
            'due_date' => '2026-08-15',
        ]);
        Carbon::setTestNow(Carbon::parse('2026-09-02 12:00:00', 'America/Santo_Domingo'));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Cobro vencido')
            ->assertSee($client->name);

        Carbon::setTestNow();
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
            ->assertSee('Faltan 7 días para el cobro')
            ->assertSee($client->name);

        CompanySetting::query()->update(['upcoming_due_days' => 6]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Faltan 7 días para el cobro');
        Carbon::setTestNow();
    }

    public function test_follow_up_projects_the_next_recurring_billing_date_across_months(): void
    {
        [$client, $catalogService, $provider] = $this->entities();
        $service = ContractedService::create([
            ...$this->payload($client, $catalogService, $provider),
            'billing_day' => 5,
            'status' => ContractedServiceStatus::Active,
        ]);
        CompanySetting::create([
            'timezone' => 'America/Santo_Domingo',
            'upcoming_due_days' => 7,
        ]);
        Charge::create([
            'contracted_service_id' => $service->id,
            'status' => ChargeStatus::Paid,
            'amount' => 50,
            'currency' => 'USD',
            'due_date' => '2026-08-05',
        ]);
        Carbon::setTestNow(Carbon::parse('2026-08-29 12:00:00', 'America/Santo_Domingo'));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Próximo vencimiento')
            ->assertSee('Faltan 7 días para el cobro')
            ->assertSee('05/09/2026')
            ->assertSee($client->name);

        Carbon::setTestNow();
    }

    public function test_follow_up_orders_upcoming_billings_by_days_remaining(): void
    {
        $this->createUpcomingService('Cliente siete días', 5, '2026-08-05');
        $this->createUpcomingService('Cliente un día', 30);
        $this->createUpcomingService('Cliente cuatro días', 2, '2026-08-02');
        CompanySetting::create([
            'timezone' => 'America/Santo_Domingo',
            'upcoming_due_days' => 7,
        ]);
        Carbon::setTestNow(Carbon::parse('2026-08-29 12:00:00', 'America/Santo_Domingo'));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder([
                'Cliente un día',
                'Cliente cuatro días',
                'Cliente siete días',
            ]);

        Carbon::setTestNow();
    }

    public function test_follow_up_groups_a_clients_services_with_the_same_upcoming_billing_date(): void
    {
        $client = Client::create(['name' => 'Cliente agrupado', 'phone' => '8090000000']);
        $this->createUpcomingService('Cliente externo', 30, null, 'Servicio central');
        $this->createUpcomingService('Cliente agrupado', 30, null, 'Servicio Z', $client);
        $this->createUpcomingService('Cliente agrupado', 30, null, 'Servicio A', $client);
        CompanySetting::create([
            'timezone' => 'America/Santo_Domingo',
            'upcoming_due_days' => 7,
        ]);
        Carbon::setTestNow(Carbon::parse('2026-08-29 12:00:00', 'America/Santo_Domingo'));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder([
                'Cliente agrupado',
                'Servicio A',
                'Cliente agrupado',
                'Servicio Z',
                'Cliente externo',
            ]);

        Carbon::setTestNow();
    }

    public function test_marking_a_promised_service_as_paid_removes_its_resolved_promise_from_follow_up(): void
    {
        [$client, $catalogService, $provider] = $this->entities();
        $client->update(['name' => 'Cliente promesa pagada']);
        $service = ContractedService::create([
            ...$this->payload($client, $catalogService, $provider),
            'billing_day' => 18,
            'status' => ContractedServiceStatus::Active,
        ]);
        Gestion::create([
            'client_id' => $client->id,
            'contracted_service_id' => $service->id,
            'type' => 'WhatsApp',
            'occurred_at' => '2026-08-15 10:00:00',
            'result' => 'Prometió pagar',
            'promised_payment_date' => '2026-08-18',
        ]);
        CompanySetting::create([
            'timezone' => 'America/Santo_Domingo',
            'upcoming_due_days' => 7,
        ]);
        Carbon::setTestNow(Carbon::parse('2026-08-29 12:00:00', 'America/Santo_Domingo'));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Promesa vencida')
            ->assertSee($client->name);

        $this->post(route('contracted-services.mark-paid', $service))
            ->assertRedirect()
            ->assertSessionHas('success');

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

    private function createUpcomingService(string $clientName, int $billingDay, ?string $paidDueDate = null, string $catalogServiceName = 'PBX', ?Client $client = null): void
    {
        $client ??= Client::create(['name' => $clientName, 'phone' => '8090000000']);
        $catalogService = CatalogService::create(['name' => $catalogServiceName, 'is_active' => true]);
        $provider = Provider::create(['name' => 'Proveedor', 'payment_method' => 'Mensual']);
        $service = ContractedService::create([
            ...$this->payload($client, $catalogService, $provider),
            'billing_day' => $billingDay,
            'status' => ContractedServiceStatus::Active,
        ]);
        if ($paidDueDate !== null) {
            Charge::create([
                'contracted_service_id' => $service->id,
                'status' => ChargeStatus::Paid,
                'amount' => 50,
                'currency' => 'USD',
                'due_date' => $paidDueDate,
            ]);
        }
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
