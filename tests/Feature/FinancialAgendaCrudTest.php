<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FinancialCommitmentFrequency;
use App\Enums\ContractedServiceStatus;
use App\Livewire\FinancialAgenda\Dashboard;
use App\Livewire\FinancialAgenda\Commitments\Index as CommitmentsIndex;
use App\Models\Beneficiary;
use App\Models\CatalogService;
use App\Models\Client;
use App\Models\CommitmentPayment;
use App\Models\ContractedService;
use App\Models\ExchangeRate;
use App\Models\FinancialCommitment;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FinancialAgendaCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_financial_agenda_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('financial-agenda.index'))->assertOk();
        $this->actingAs($user)->get(route('financial-agenda.beneficiaries.index'))->assertOk();
        $this->actingAs($user)->get(route('financial-agenda.commitments.index'))->assertOk();
    }

    public function test_authenticated_user_can_create_and_update_a_beneficiary(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('financial-agenda.beneficiaries.store'), [
            'name' => 'Banco Popular',
            'type' => 'Banco',
            'is_active' => '1',
            'observations' => 'Pago desde cuenta empresarial.',
        ]);

        $beneficiary = Beneficiary::query()->firstOrFail();
        $response->assertRedirect(route('financial-agenda.beneficiaries.index'));
        $this->assertSame('Banco Popular', $beneficiary->name);

        $this->actingAs($user)->put(route('financial-agenda.beneficiaries.update', $beneficiary), [
            'name' => 'Banco Popular actualizado',
            'type' => 'Institución bancaria',
            'is_active' => '0',
        ])->assertRedirect(route('financial-agenda.beneficiaries.index'));

        $this->assertDatabaseHas('beneficiaries', [
            'id' => $beneficiary->id,
            'name' => 'Banco Popular actualizado',
            'is_active' => 0,
        ]);
    }

    public function test_authenticated_user_can_create_and_update_a_financial_commitment(): void
    {
        $user = User::factory()->create();
        $beneficiary = Beneficiary::query()->create(['name' => 'Claro', 'type' => 'Telecomunicaciones']);

        $payload = [
            'beneficiary_id' => $beneficiary->id,
            'name' => 'Internet oficina',
            'category' => 'Internet',
            'frequency' => FinancialCommitmentFrequency::Monthly->value,
            'suggested_amount' => '150.00',
            'has_cutoff' => '0',
            'due_day' => '10',
            'is_active' => '1',
        ];

        $response = $this->actingAs($user)->post(route('financial-agenda.commitments.store'), $payload);
        $commitment = FinancialCommitment::query()->firstOrFail();

        $response->assertRedirect(route('financial-agenda.commitments.index'));
        $this->assertSame(FinancialCommitmentFrequency::Monthly, $commitment->frequency);

        $this->actingAs($user)->put(route('financial-agenda.commitments.update', $commitment), [
            ...$payload,
            'name' => 'Internet oficina actualizado',
            'has_cutoff' => '1',
            'cutoff_day' => '3',
            'due_day' => '12',
        ])->assertRedirect(route('financial-agenda.commitments.index'));

        $this->assertDatabaseHas('financial_commitments', [
            'id' => $commitment->id,
            'name' => 'Internet oficina actualizado',
            'has_cutoff' => 1,
            'cutoff_day' => 3,
            'due_day' => 12,
        ]);
    }

    public function test_cutoff_day_is_required_when_commitment_has_cutoff(): void
    {
        $user = User::factory()->create();
        $beneficiary = Beneficiary::query()->create(['name' => 'Visa', 'type' => 'Tarjeta']);

        $this->actingAs($user)
            ->from(route('financial-agenda.commitments.create'))
            ->post(route('financial-agenda.commitments.store'), [
                'beneficiary_id' => $beneficiary->id,
                'name' => 'Tarjeta Visa',
                'category' => 'Tarjeta de crédito',
                'frequency' => 'monthly',
                'has_cutoff' => '1',
                'due_day' => '25',
                'is_active' => '1',
            ])
            ->assertRedirect(route('financial-agenda.commitments.create'))
            ->assertSessionHasErrors('cutoff_day');
    }

    public function test_dashboard_renders_commitments_with_filters(): void
    {
        $user = User::factory()->create();
        $beneficiary = Beneficiary::query()->create(['name' => 'AWS', 'type' => 'Infraestructura']);
        FinancialCommitment::query()->create([
            'beneficiary_id' => $beneficiary->id,
            'name' => 'Servidor principal',
            'category' => 'Hosting',
            'frequency' => FinancialCommitmentFrequency::Monthly,
            'suggested_amount' => 100,
            'due_day' => now()->day,
            'is_active' => true,
        ]);
        FinancialCommitment::query()->create([
            'beneficiary_id' => $beneficiary->id,
            'name' => 'Pago posterior',
            'category' => 'Hosting',
            'frequency' => FinancialCommitmentFrequency::Monthly,
            'suggested_amount' => 250,
            'due_day' => min(now()->daysInMonth, now()->day + 10),
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSee('Servidor principal')
            ->assertSee('350.00')
            ->assertSeeInOrder(['Servidor principal', 'Pago posterior'])
            ->set('status', 'pending')
            ->assertSee('Servidor principal')
            ->set('beneficiaryId', (string) $beneficiary->id)
            ->assertSee('AWS');
    }

    public function test_commitments_index_displays_its_summary(): void
    {
        $user = User::factory()->create();
        $beneficiary = Beneficiary::query()->create(['name' => 'Proveedor de prueba', 'type' => 'Servicios']);

        FinancialCommitment::query()->create([
            'beneficiary_id' => $beneficiary->id,
            'name' => 'Compromiso activo',
            'category' => 'Prueba',
            'frequency' => FinancialCommitmentFrequency::Monthly,
            'suggested_amount' => 125,
            'due_day' => 10,
            'is_active' => true,
        ]);
        FinancialCommitment::query()->create([
            'beneficiary_id' => $beneficiary->id,
            'name' => 'Compromiso inactivo',
            'category' => 'Prueba',
            'frequency' => FinancialCommitmentFrequency::Monthly,
            'suggested_amount' => 75,
            'due_day' => 15,
            'is_active' => false,
        ]);

        Livewire::actingAs($user)
            ->test(CommitmentsIndex::class)
            ->assertSee('Total de compromisos')
            ->assertSee('Compromisos activos')
            ->assertSee('Monto total')
            ->assertSee('125.00');
    }

    public function test_dashboard_deducts_all_active_commitments_from_the_monthly_benefit(): void
    {
        $user = User::factory()->create();
        $beneficiary = Beneficiary::query()->create(['name' => 'Beneficiario de prueba', 'type' => 'Servicios']);
        $client = Client::query()->create(['name' => 'Cliente de prueba', 'phone' => '8090000000']);
        $catalogService = CatalogService::query()->create(['name' => 'Servicio de prueba', 'is_active' => true]);
        $provider = Provider::query()->create(['name' => 'Proveedor de prueba', 'payment_method' => 'Mensual']);

        ContractedService::query()->create([
            'client_id' => $client->id,
            'catalog_service_id' => $catalogService->id,
            'provider_id' => $provider->id,
            'price' => 1000,
            'price_currency' => 'USD',
            'cost' => 200,
            'cost_currency' => 'USD',
            'billing_day' => 15,
            'starts_at' => now()->startOfMonth(),
            'status' => ContractedServiceStatus::Active,
        ]);
        FinancialCommitment::query()->create([
            'beneficiary_id' => $beneficiary->id,
            'name' => 'Compromiso mensual activo',
            'category' => 'Prueba',
            'frequency' => FinancialCommitmentFrequency::Monthly,
            'suggested_amount' => 10000,
            'due_day' => 10,
            'is_active' => true,
        ]);
        ExchangeRate::query()->create(['rate' => 58, 'effective_date' => now()->toDateString()]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSee('RD$ 36,400.00')
            ->assertSee('Compromisos activos: RD$ 10,000.00');
    }

    public function test_dashboard_can_register_a_payment_without_leaving_the_screen(): void
    {
        $user = User::factory()->create();
        $beneficiary = Beneficiary::query()->create(['name' => 'Netflix', 'type' => 'Suscripción']);
        $commitment = FinancialCommitment::query()->create([
            'beneficiary_id' => $beneficiary->id,
            'name' => 'Suscripción mensual',
            'category' => 'Suscripciones',
            'frequency' => FinancialCommitmentFrequency::Monthly,
            'suggested_amount' => 20,
            'due_day' => now()->day,
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->call('openPaymentForm', CommitmentPayment::query()->where('financial_commitment_id', $commitment->id)->firstOrFail()->id)
            ->set('paymentDate', now()->toDateString())
            ->set('amountPaid', '20.00')
            ->set('paymentObservations', 'Pago confirmado.')
            ->call('savePayment')
            ->assertSet('paymentCommitmentId', null)
            ->assertSee('Pago registrado para la obligación seleccionada.');

        $this->assertDatabaseHas('commitment_payments', [
            'financial_commitment_id' => $commitment->id,
            'status' => 'paid',
            'amount_paid' => 20,
        ]);
        $this->assertSame(1, CommitmentPayment::query()->where('financial_commitment_id', $commitment->id)->where('status', 'paid')->count());
        $this->assertSame(1, CommitmentPayment::query()->where('financial_commitment_id', $commitment->id)->firstOrFail()->entries()->count());
    }

    public function test_dashboard_orders_overdue_pending_and_paid_groups_in_that_order(): void
    {
        $user = User::factory()->create();
        $beneficiary = Beneficiary::query()->create(['name' => 'Banco de prueba', 'type' => 'Banco']);
        $today = now()->day;

        $overdue = FinancialCommitment::query()->create([
            'beneficiary_id' => $beneficiary->id,
            'name' => 'Compromiso vencido',
            'category' => 'Prueba',
            'frequency' => FinancialCommitmentFrequency::Monthly,
            'due_day' => max(1, $today - 2),
            'is_active' => true,
        ]);
        FinancialCommitment::query()->create([
            'beneficiary_id' => $beneficiary->id,
            'name' => 'Compromiso pendiente',
            'category' => 'Prueba',
            'frequency' => FinancialCommitmentFrequency::Monthly,
            'due_day' => min(now()->daysInMonth, $today + 2),
            'is_active' => true,
        ]);
        $paid = FinancialCommitment::query()->create([
            'beneficiary_id' => $beneficiary->id,
            'name' => 'Compromiso pagado',
            'category' => 'Prueba',
            'frequency' => FinancialCommitmentFrequency::Monthly,
            'due_day' => $today,
            'is_active' => true,
        ]);
        CommitmentPayment::query()->create([
            'financial_commitment_id' => $paid->id,
            'period_start' => now()->startOfMonth(),
            'due_date' => now()->startOfMonth()->addDays($today - 1),
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSeeInOrder(['Compromiso vencido', 'Compromiso pendiente', 'Compromiso pagado']);
    }

    public function test_dashboard_can_save_a_dollar_exchange_rate(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('exchangeRate', '60.2500')
            ->set('exchangeRateDate', now()->toDateString())
            ->call('saveExchangeRate')
            ->assertSee('Tasa del dólar guardada correctamente.');

        $this->assertSame(1, ExchangeRate::query()->where('rate', 60.25)->whereDate('effective_date', now()->toDateString())->count());
    }
}
