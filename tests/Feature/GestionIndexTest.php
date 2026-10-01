<?php

namespace Tests\Feature;

use App\Enums\ChargeStatus;
use App\Enums\ContractedServiceStatus;
use App\Models\CatalogService;
use App\Models\Charge;
use App\Models\Client;
use App\Models\ContractedService;
use App\Models\Gestion;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GestionIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_gestions_index_displays_the_related_contracted_service(): void
    {
        $this->actingAs(User::factory()->create());

        $client = Client::create(['name' => 'Cliente con varios servicios', 'phone' => '8090000000']);
        $provider = Provider::create(['name' => 'Proveedor', 'payment_method' => 'Mensual']);
        $managedPbx = CatalogService::create(['name' => 'PBX administrada', 'is_active' => true]);
        $backupVps = CatalogService::create(['name' => 'VPS de respaldo', 'is_active' => true]);

        $managedPbxContract = $this->createContractedService(
            $client,
            $managedPbx,
            $provider,
            'Central principal del cliente',
            '203.0.113.25',
        );
        $this->createContractedService(
            $client,
            $backupVps,
            $provider,
            'Servidor secundario',
            '203.0.113.99',
        );

        Gestion::create([
            'client_id' => $client->id,
            'contracted_service_id' => $managedPbxContract->id,
            'type' => 'WhatsApp',
            'occurred_at' => '2026-10-01 09:30:00',
            'result' => 'Cliente contactado',
        ]);

        $this->get(route('gestions.index'))
            ->assertOk()
            ->assertSee('Servicio contratado')
            ->assertSee('PBX administrada')
            ->assertSee('Central principal del cliente')
            ->assertSee('203.0.113.25')
            ->assertDontSee('VPS de respaldo')
            ->assertDontSee('203.0.113.99');
    }

    public function test_gestions_index_displays_the_exact_paid_period(): void
    {
        $this->actingAs(User::factory()->create());

        $client = Client::create(['name' => 'Cliente pago tardío', 'phone' => '8090000001']);
        $provider = Provider::create(['name' => 'Proveedor pago tardío', 'payment_method' => 'Mensual']);
        $catalogService = CatalogService::create(['name' => 'Dialer pago tardío', 'is_active' => true]);
        $service = $this->createContractedService($client, $catalogService, $provider, 'Campaña principal', '203.0.113.30');
        $charge = Charge::create([
            'contracted_service_id' => $service->id,
            'status' => ChargeStatus::Paid,
            'amount' => 75,
            'currency' => 'USD',
            'due_date' => '2026-09-15',
        ]);

        Gestion::create([
            'client_id' => $client->id,
            'contracted_service_id' => $service->id,
            'charge_id' => $charge->id,
            'type' => 'Pago recibido',
            'occurred_at' => '2026-10-01 12:58:00',
            'result' => 'Pago confirmado',
        ]);

        $this->get(route('gestions.index'))
            ->assertOk()
            ->assertSee('Período pagado')
            ->assertSee('Cobro del 15/09/2026')
            ->assertSee('USD 75.00');
    }

    public function test_gestions_index_resolves_the_period_for_historical_payment_gestions(): void
    {
        $this->actingAs(User::factory()->create());

        $client = Client::create(['name' => 'Cliente histórico', 'phone' => '8090000002']);
        $provider = Provider::create(['name' => 'Proveedor histórico', 'payment_method' => 'Mensual']);
        $catalogService = CatalogService::create(['name' => 'PBX histórico', 'is_active' => true]);
        $service = $this->createContractedService($client, $catalogService, $provider, 'Central histórica', '203.0.113.31');
        Charge::create([
            'contracted_service_id' => $service->id,
            'status' => ChargeStatus::Paid,
            'amount' => 90,
            'currency' => 'USD',
            'due_date' => '2026-10-20',
        ]);

        Gestion::create([
            'client_id' => $client->id,
            'contracted_service_id' => $service->id,
            'type' => 'Pago recibido',
            'occurred_at' => '2026-10-01 12:58:00',
            'result' => 'Pago histórico confirmado',
        ]);

        $this->get(route('gestions.index'))
            ->assertOk()
            ->assertSee('Cobro del 20/10/2026')
            ->assertSee('USD 90.00');
    }

    private function createContractedService(
        Client $client,
        CatalogService $catalogService,
        Provider $provider,
        string $description,
        string $ip,
    ): ContractedService {
        return ContractedService::create([
            'client_id' => $client->id,
            'catalog_service_id' => $catalogService->id,
            'provider_id' => $provider->id,
            'price' => 50,
            'price_currency' => 'USD',
            'cost' => 20,
            'cost_currency' => 'USD',
            'billing_day' => 15,
            'status' => ContractedServiceStatus::Active,
            'starts_at' => '2026-09-01',
            'observations' => $description,
            'ip' => $ip,
        ]);
    }
}
