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

class OperationalDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_operational_dashboard_only_shows_unpaid_charges_for_today(): void
    {
        [$client, $service] = $this->serviceEntities('Cliente con cobros');

        $pendingCharge = Charge::create([
            'contracted_service_id' => $service->id,
            'status' => ChargeStatus::Pending,
            'amount' => 75,
            'currency' => 'USD',
            'due_date' => now()->toDateString(),
        ]);

        $paidCharge = Charge::create([
            'contracted_service_id' => $service->id,
            'status' => ChargeStatus::Paid,
            'amount' => 50,
            'currency' => 'USD',
            'due_date' => now()->toDateString(),
        ]);

        $response = $this->get(route('dashboard.operational'));

        $response->assertOk()
            ->assertViewHas('todayCharges', fn ($charges): bool => $charges->contains('id', $pendingCharge->id)
                && ! $charges->contains('id', $paidCharge->id))
            ->assertViewHas('overdue', fn ($charges): bool => $charges->isEmpty())
            ->assertSee($client->name)
            ->assertSee('USD 75.00')
            ->assertDontSee('USD 50.00');
    }

    public function test_operational_dashboard_uses_next_follow_up_for_tomorrow_reminders(): void
    {
        [$tomorrowClient, $tomorrowService] = $this->serviceEntities('Cliente recordatorio mañana');
        [$promiseClient, $promiseService] = $this->serviceEntities('Cliente promesa futura');

        $tomorrow = now()->addDay();

        $tomorrowReminder = Gestion::create([
            'client_id' => $tomorrowClient->id,
            'contracted_service_id' => $tomorrowService->id,
            'type' => 'WhatsApp',
            'occurred_at' => now(),
            'result' => 'Confirmar documentación',
            'next_follow_up_at' => $tomorrow->copy()->setTime(11, 30),
        ]);

        $futurePromise = Gestion::create([
            'client_id' => $promiseClient->id,
            'contracted_service_id' => $promiseService->id,
            'type' => 'WhatsApp',
            'occurred_at' => now(),
            'result' => 'Prometió pagar',
            'promised_payment_date' => $tomorrow->toDateString(),
            'next_follow_up_at' => $tomorrow->copy()->addDay()->setTime(9, 0),
        ]);

        $response = $this->get(route('dashboard.operational'));

        $response->assertOk()
            ->assertViewHas('tomorrow', fn ($reminders): bool => $reminders->contains('id', $tomorrowReminder->id)
                && ! $reminders->contains('id', $futurePromise->id))
            ->assertSee($tomorrowClient->name)
            ->assertSee('11:30')
            ->assertDontSee($promiseClient->name);
    }

    private function serviceEntities(string $clientName): array
    {
        $client = Client::create(['name' => $clientName, 'phone' => '8090000000']);
        $catalogService = CatalogService::create(['name' => 'PBX '.$clientName, 'is_active' => true]);
        $provider = Provider::create(['name' => 'Proveedor '.$clientName, 'payment_method' => 'Mensual']);
        $service = ContractedService::create([
            'client_id' => $client->id,
            'catalog_service_id' => $catalogService->id,
            'provider_id' => $provider->id,
            'price' => 50,
            'price_currency' => 'USD',
            'cost' => 20,
            'cost_currency' => 'USD',
            'billing_day' => 15,
            'status' => ContractedServiceStatus::Active,
            'starts_at' => now()->subMonth()->toDateString(),
        ]);

        return [$client, $service];
    }
}
