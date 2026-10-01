<?php

namespace Tests\Feature;

use App\Enums\ContractedServiceStatus;
use App\Models\CatalogService;
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
