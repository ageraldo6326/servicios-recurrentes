<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CommercialInvoiceStatus;
use App\Enums\CommitmentPaymentStatus;
use App\Enums\FinancialCommitmentFrequency;
use App\Enums\PaymentStatus;
use App\Enums\UnplannedExpenseStatus;
use App\Models\Beneficiary;
use App\Models\Client;
use App\Models\CommercialInvoice;
use App\Models\CommitmentPayment;
use App\Models\CommitmentPaymentEntry;
use App\Models\ExchangeRate;
use App\Models\FinancialCommitment;
use App\Models\Payment;
use App\Models\UnplannedExpense;
use App\Models\User;
use App\Services\FinancialHistoryService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FinancialHistoryDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_converts_peso_expenses_using_the_current_rate_for_every_month(): void
    {
        $date = CarbonImmutable::parse('2026-01-15');
        $user = User::factory()->create();
        ExchangeRate::query()->create(['rate' => 50, 'effective_date' => '2026-01-01']);
        Payment::query()->create(['amount' => 100, 'currency' => 'USD', 'received_at' => $date, 'status' => PaymentStatus::Validated]);
        Payment::query()->create(['amount' => 500, 'currency' => 'USD', 'received_at' => $date, 'status' => PaymentStatus::Validated]);

        $client = Client::query()->create(['name' => 'Cliente de prueba', 'phone' => '8090000000']);
        $invoice = CommercialInvoice::query()->create([
            'client_id' => $client->id,
            'created_by' => $user->id,
            'number' => 'FAC-PRUEBA-001',
            'issue_date' => $date,
            'due_date' => $date,
            'currency' => 'USD',
            'status' => CommercialInvoiceStatus::Paid,
            'paid_at' => $date,
        ]);
        $invoice->items()->create(['concept' => 'Trabajo único', 'quantity' => 1, 'unit_price' => 50, 'total' => 50]);

        $beneficiary = Beneficiary::query()->create(['name' => 'Proveedor de prueba', 'type' => 'Proveedor']);
        $commitment = FinancialCommitment::query()->create([
            'beneficiary_id' => $beneficiary->id,
            'name' => 'Compromiso de prueba',
            'category' => 'Operación',
            'frequency' => FinancialCommitmentFrequency::Monthly,
            'due_day' => 15,
        ]);
        $occurrence = CommitmentPayment::query()->create([
            'financial_commitment_id' => $commitment->id,
            'period_start' => '2026-01-01',
            'due_date' => '2026-01-15',
            'status' => CommitmentPaymentStatus::PartiallyPaid,
        ]);
        CommitmentPaymentEntry::query()->create(['commitment_payment_id' => $occurrence->id, 'paid_at' => $date, 'amount' => 1000]);
        UnplannedExpense::query()->create(['amount' => 250, 'expense_date' => $date, 'status' => UnplannedExpenseStatus::Paid]);

        $report = app(FinancialHistoryService::class)->report($date, $date, (float) ExchangeRate::query()->value('rate'));
        $january = collect($report['points'])->firstWhere('key', '2026-01');

        $this->assertSame(600.0, $january['recurring']);
        $this->assertSame(50.0, $january['invoices']);
        $this->assertSame(20.0, $january['commitments']);
        $this->assertSame(5.0, $january['unplanned']);
        $this->assertSame(625.0, $january['net']);
        $this->assertGreaterThan(0, $january['chart_income_height']);
        $this->assertGreaterThan(0, $january['chart_expense_height']);
    }

    public function test_authenticated_user_can_view_cash_flow_dashboard(): void
    {
        ExchangeRate::query()->create(['rate' => 50, 'effective_date' => now()->toDateString()]);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard.cash-flow'))
            ->assertOk()
            ->assertSee('Flujo histórico')
            ->assertSee('Ingresos vs. egresos')
            ->assertSee('DOP 50.0000 por USD')
            ->assertSee('data-chart-bar="recurring"', false);
    }
}
