<?php

namespace Tests\Feature;

use App\Enums\ChargeStatus;
use App\Enums\ContractedServiceStatus;
use App\Enums\PaymentStatus;
use App\Models\CatalogService;
use App\Models\Charge;
use App\Models\Client;
use App\Models\ContractedService;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_payments_index_identifies_the_client_and_contracted_service(): void
    {
        $this->actingAs(User::factory()->create());

        $client = Client::create(['name' => 'Cliente identificado', 'phone' => '8090000000']);
        $provider = Provider::create(['name' => 'Proveedor', 'payment_method' => 'Mensual']);
        $catalogService = CatalogService::create(['name' => 'Dialer empresarial', 'is_active' => true]);
        $contractedService = ContractedService::create([
            'client_id' => $client->id,
            'catalog_service_id' => $catalogService->id,
            'provider_id' => $provider->id,
            'price' => 100,
            'price_currency' => 'USD',
            'cost' => 40,
            'cost_currency' => 'USD',
            'ip' => '198.51.100.40',
            'billing_day' => 15,
            'status' => ContractedServiceStatus::Active,
            'starts_at' => '2026-09-01',
            'observations' => 'Campaña de ventas principal',
        ]);
        $charge = Charge::create([
            'contracted_service_id' => $contractedService->id,
            'status' => ChargeStatus::Pending,
            'amount' => 100,
            'currency' => 'USD',
            'due_date' => '2026-10-15',
        ]);
        $payment = Payment::create([
            'amount' => 100,
            'currency' => 'USD',
            'received_at' => '2026-10-01',
            'status' => PaymentStatus::Pending,
        ]);
        $payment->charges()->attach($charge, ['amount' => 100, 'currency' => 'USD']);

        $this->get(route('payments.index'))
            ->assertOk()
            ->assertSee('Cliente / servicio contratado')
            ->assertSee('Cliente identificado')
            ->assertSee('Dialer empresarial')
            ->assertSee('Campaña de ventas principal')
            ->assertSee('198.51.100.40');
    }

    public function test_payments_index_marks_payments_without_an_assigned_service(): void
    {
        $this->actingAs(User::factory()->create());

        Payment::create([
            'amount' => 75,
            'currency' => 'USD',
            'received_at' => '2026-10-01',
            'status' => PaymentStatus::Pending,
        ]);

        $this->get(route('payments.index'))
            ->assertOk()
            ->assertSee('Sin servicio asignado')
            ->assertSee('Este pago aún no está imputado a un cobro.');
    }
}
