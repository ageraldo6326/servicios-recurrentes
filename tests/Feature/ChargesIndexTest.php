<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ChargeStatus;
use App\Enums\ContractedServiceStatus;
use App\Livewire\Charges\Index;
use App\Models\CatalogService;
use App\Models\Charge;
use App\Models\Client;
use App\Models\ContractedService;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChargesIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_searches_charges_by_client_name_or_service_ip_and_shows_newest_first(): void
    {
        $this->actingAs(User::factory()->create());

        $oldCharge = $this->createCharge('Cliente anterior', '192.0.2.10');
        $recentCharge = $this->createCharge('Cliente con IP', '198.51.100.24');
        $oldCharge->forceFill(['created_at' => now()->subDay(), 'updated_at' => now()->subDay()])->save();
        $recentCharge->forceFill(['created_at' => now(), 'updated_at' => now()])->save();

        Livewire::test(Index::class)
            ->assertSeeInOrder(['Cliente con IP', 'Cliente anterior'])
            ->set('search', '198.51.100.24')
            ->assertSee('Cliente con IP')
            ->assertSee('198.51.100.24')
            ->assertDontSee('Cliente anterior')
            ->set('search', 'Cliente anterior')
            ->assertSee('Cliente anterior')
            ->assertSee('192.0.2.10')
            ->assertDontSee('Cliente con IP');
    }

    private function createCharge(string $clientName, string $ip): Charge
    {
        $client = Client::query()->create(['name' => $clientName, 'phone' => '8090000000']);
        $catalogService = CatalogService::query()->create(['name' => 'PBX', 'is_active' => true]);
        $provider = Provider::query()->create(['name' => 'Proveedor', 'payment_method' => 'Mensual']);
        $service = ContractedService::query()->create([
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
            'starts_at' => '2026-07-01',
        ]);

        return Charge::query()->create([
            'contracted_service_id' => $service->id,
            'status' => ChargeStatus::Pending,
            'amount' => 50,
            'currency' => 'USD',
            'due_date' => '2026-08-15',
        ]);
    }
}
