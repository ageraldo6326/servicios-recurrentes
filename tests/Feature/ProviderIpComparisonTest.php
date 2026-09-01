<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ContractedServiceStatus;
use App\Livewire\ProviderIpComparison\CompareForm;
use App\Models\CatalogService;
use App\Models\Client;
use App\Models\ContractedService;
use App\Models\Provider;
use App\Models\User;
use App\Services\ProviderIpComparison\Ipv4Extractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class ProviderIpComparisonTest extends TestCase
{
    use RefreshDatabase;

    public function test_extractor_keeps_only_valid_unique_ipv4_addresses(): void
    {
        $result = app(Ipv4Extractor::class)->extract('Válida 192.0.2.10, otra 192.0.2.10, inválida 999.10.20.30 e IPv6 2001:db8::1.');

        $this->assertSame(['192.0.2.10'], $result['ips']);
        $this->assertSame(1, $result['duplicate_count']);
        $this->assertContains(['value' => '999.10.20.30', 'reason' => 'Formato IPv4 inválido'], $result['discarded']);
        $this->assertContains(['value' => '2001:db8::1', 'reason' => 'IPv6 fuera de alcance inicial'], $result['discarded']);
    }

    public function test_comparison_shows_differences_without_changing_contracted_services(): void
    {
        $user = User::factory()->create();
        $provider = Provider::create(['name' => 'Vultr', 'payment_method' => 'Mensual']);
        $otherProvider = Provider::create(['name' => 'AMDY', 'payment_method' => 'Mensual']);
        $client = Client::create(['name' => 'Cliente IP', 'phone' => '8090000000']);
        $catalogService = CatalogService::create(['name' => 'VPS', 'is_active' => true]);

        $matchingService = $this->createService($client, $catalogService, $provider, '192.0.2.10');
        $missingService = $this->createService($client, $catalogService, $provider, '192.0.2.20');
        $this->createService($client, $catalogService, $provider, null);
        $this->createService($client, $catalogService, $otherProvider, '192.0.2.30');
        $originalCount = ContractedService::query()->count();

        Livewire::actingAs($user)
            ->test(CompareForm::class)
            ->set('providerId', $provider->id)
            ->set('pastedText', 'Instancias: 192.0.2.10, 192.0.2.40 y 192.0.2.40.')
            ->call('compare')
            ->assertSet('pastedText', '')
            ->assertSet('comparison.summary.valid_pasted_ips', 2)
            ->assertSet('comparison.summary.matching_ips', 1)
            ->assertSet('comparison.summary.provider_only_ips', 1)
            ->assertSet('comparison.summary.service_only_ips', 1)
            ->assertSet('comparison.summary.services_without_internal_ip', 1)
            ->assertSee('192.0.2.40')
            ->assertSee('192.0.2.20');

        $this->assertSame($originalCount, ContractedService::query()->count());
        $this->assertDatabaseHas('contracted_services', ['id' => $matchingService->id, 'ip' => '192.0.2.10']);
        $this->assertDatabaseHas('contracted_services', ['id' => $missingService->id, 'ip' => '192.0.2.20']);
    }

    public function test_authenticated_users_can_open_the_comparison_screen(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('provider-ip-comparison.index'))
            ->assertOk()
            ->assertSee('Comparar IP de proveedor');
    }

    private function createService(Client $client, CatalogService $catalogService, Provider $provider, ?string $ip): ContractedService
    {
        return ContractedService::create([
            'client_id' => $client->id,
            'catalog_service_id' => $catalogService->id,
            'provider_id' => $provider->id,
            'price' => 50,
            'price_currency' => 'USD',
            'cost' => 20,
            'cost_currency' => 'USD',
            'ip' => $ip,
            'billing_day' => 15,
            'status' => ContractedServiceStatus::Active,
            'starts_at' => '2026-08-01',
        ]);
    }
}
