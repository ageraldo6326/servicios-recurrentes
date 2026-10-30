<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ChargeStatus;
use App\Enums\ContractedServiceStatus;
use App\Enums\PaymentStatus;
use App\Models\CatalogService;
use App\Models\Charge;
use App\Models\Client;
use App\Models\ContractedService;
use App\Models\Gestion;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PaymentRejectionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        Carbon::setTestNow(Carbon::parse('2026-10-30 12:00:00', 'America/Santo_Domingo'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_validated_payment_can_be_rejected_without_deleting_its_history(): void
    {
        [$service, $charge, $payment] = $this->paidService();

        $this->post(route('payments.reject', $payment), [
            'rejection_reason' => 'El cliente confirmó que este pago nunca fue realizado.',
        ])->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::Rejected->value,
            'rejection_reason' => 'El cliente confirmó que este pago nunca fue realizado.',
            'rejected_by' => $this->user->id,
        ]);
        $this->assertNotNull($payment->fresh()->rejected_at);
        $this->assertDatabaseHas('payment_allocations', [
            'payment_id' => $payment->id,
            'charge_id' => $charge->id,
            'amount' => 100,
        ]);
        $this->assertDatabaseHas('charges', [
            'id' => $charge->id,
            'status' => ChargeStatus::Pending->value,
        ]);
        $this->assertDatabaseHas('gestions', [
            'contracted_service_id' => $service->id,
            'charge_id' => $charge->id,
            'type' => 'Pago anulado',
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Cliente con pago incorrecto')
            ->assertSee('Cobro de hoy');
    }

    public function test_rejecting_one_payment_recalculates_a_partially_paid_charge(): void
    {
        [, $charge, $payment] = $this->paidService();
        $otherPayment = Payment::query()->create([
            'amount' => 40,
            'currency' => 'USD',
            'received_at' => '2026-10-29',
            'status' => PaymentStatus::Validated,
            'validated_at' => now(),
        ]);
        $otherPayment->charges()->attach($charge->id, ['amount' => 40, 'currency' => 'USD']);

        $this->post(route('payments.reject', $payment), [
            'rejection_reason' => 'Registro duplicado.',
        ])->assertRedirect();

        $this->assertDatabaseHas('charges', [
            'id' => $charge->id,
            'status' => ChargeStatus::Partial->value,
        ]);
    }

    public function test_rejected_allocation_does_not_prevent_registering_the_real_payment(): void
    {
        [$service, $charge, $payment] = $this->paidService();

        $this->post(route('payments.reject', $payment), [
            'rejection_reason' => 'Pago inexistente.',
        ])->assertRedirect();

        $this->post(route('contracted-services.mark-paid', $service))
            ->assertRedirect()
            ->assertSessionHas('success', 'Pago registrado para el período con vencimiento 30/10/2026. El servicio quedó preparado para el próximo período.');

        $this->assertDatabaseHas('charges', [
            'id' => $charge->id,
            'status' => ChargeStatus::Paid->value,
        ]);
        $this->assertSame(1, $charge->payments()->where('payments.status', PaymentStatus::Rejected->value)->count());
        $this->assertSame(1, $charge->payments()->where('payments.status', PaymentStatus::Validated->value)->count());
        $this->assertSame(200.0, (float) $charge->payments()->sum('payment_allocations.amount'));
    }

    public function test_rejection_requires_a_reason_and_a_validated_payment(): void
    {
        [, , $payment] = $this->paidService();

        $this->from(route('payments.index'))
            ->post(route('payments.reject', $payment))
            ->assertRedirect(route('payments.index'))
            ->assertSessionHasErrors('rejection_reason');

        $this->post(route('payments.reject', $payment), [
            'rejection_reason' => 'Pago inexistente.',
        ])->assertRedirect();

        $this->post(route('payments.validate', $payment))->assertStatus(422);
        $this->post(route('payments.reject', $payment), [
            'rejection_reason' => 'Segundo intento.',
        ])->assertSessionHasErrors('payment');
    }

    public function test_payments_index_displays_rejection_audit_and_action(): void
    {
        [, , $payment] = $this->paidService();

        $this->get(route('payments.index'))
            ->assertOk()
            ->assertSee('Anular pago')
            ->assertSee(route('payments.reject', $payment), false);

        $this->post(route('payments.reject', $payment), [
            'rejection_reason' => 'Pago creado por error de período.',
        ])->assertRedirect();

        $this->get(route('payments.index'))
            ->assertOk()
            ->assertSee('Anulado')
            ->assertSee('Pago creado por error de período.')
            ->assertSee($this->user->name)
            ->assertDontSee('action="'.route('payments.reject', $payment).'"', false);
    }

    /** @return array{ContractedService, Charge, Payment} */
    private function paidService(): array
    {
        $client = Client::query()->create(['name' => 'Cliente con pago incorrecto', 'phone' => '8090000000']);
        $provider = Provider::query()->create(['name' => 'Proveedor', 'payment_method' => 'Mensual']);
        $catalogService = CatalogService::query()->create(['name' => 'PBX', 'is_active' => true]);
        $service = ContractedService::query()->create([
            'client_id' => $client->id,
            'catalog_service_id' => $catalogService->id,
            'provider_id' => $provider->id,
            'price' => 100,
            'price_currency' => 'USD',
            'cost' => 40,
            'cost_currency' => 'USD',
            'billing_day' => 30,
            'status' => ContractedServiceStatus::Active,
            'starts_at' => '2026-09-30',
        ]);
        $charge = Charge::query()->create([
            'contracted_service_id' => $service->id,
            'status' => ChargeStatus::Paid,
            'amount' => 100,
            'currency' => 'USD',
            'due_date' => '2026-10-30',
        ]);
        $payment = Payment::query()->create([
            'amount' => 100,
            'currency' => 'USD',
            'received_at' => '2026-10-04',
            'status' => PaymentStatus::Validated,
            'validated_at' => '2026-10-04 08:31:43',
        ]);
        $payment->charges()->attach($charge->id, ['amount' => 100, 'currency' => 'USD']);
        Gestion::query()->create([
            'client_id' => $client->id,
            'contracted_service_id' => $service->id,
            'charge_id' => $charge->id,
            'type' => 'Pago recibido',
            'occurred_at' => '2026-10-04 08:31:43',
            'result' => 'Pago registrado.',
        ]);

        return [$service, $charge, $payment];
    }
}
