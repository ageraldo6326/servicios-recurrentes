<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CommercialInvoiceStatus;
use App\Enums\CommercialQuoteStatus;
use App\Models\Client;
use App\Models\CommercialInvoice;
use App\Models\CommercialQuote;
use App\Models\User;
use App\Services\CommercialDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercialAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_separates_issued_paid_pending_and_overdue_invoices(): void
    {
        $user = User::factory()->create();
        $client = Client::query()->create(['name' => 'Cliente comercial', 'phone' => '8090000000']);
        $periodStart = now()->startOfMonth();
        $periodEnd = now()->endOfMonth();

        $this->invoice($client, $user, 'FAC-2026-0001', CommercialInvoiceStatus::Paid, $periodStart, $periodStart, 150);
        $this->invoice($client, $user, 'FAC-2026-0002', CommercialInvoiceStatus::Pending, $periodStart, $periodEnd, 200);
        $this->invoice($client, $user, 'FAC-2026-0003', CommercialInvoiceStatus::Overdue, $periodStart, $periodStart->copy()->subDay(), 75);

        $response = $this->actingAs($user)->get(route('commercial.dashboard', [
            'date_from' => $periodStart->toDateString(),
            'date_to' => $periodEnd->toDateString(),
        ]));

        $response->assertOk()
            ->assertViewHas('invoiceSummary', function (array $summary): bool {
                $this->assertSame(3, $summary['issued_count']);
                $this->assertSame(1, $summary['paid_count']);
                $this->assertSame(1, $summary['pending_count']);
                $this->assertSame(1, $summary['overdue_count']);
                $this->assertSame(150.0, $summary['paid_totals']['USD']);
                $this->assertSame(200.0, $summary['pending_totals']['USD']);

                return true;
            })
            ->assertSee('Facturas por gestionar')
            ->assertSee('FAC-2026-0003');
    }

    public function test_paid_invoice_receives_a_payment_date_when_saved(): void
    {
        $user = User::factory()->create();
        $client = Client::query()->create(['name' => 'Cliente pagado', 'phone' => '8090000001']);

        $invoice = app(CommercialDocumentService::class)->save(new CommercialInvoice, [
            'client_id' => $client->id,
            'number' => 'FAC-2026-0100',
            'issue_date' => now()->toDateString(),
            'currency' => 'USD',
            'discount' => 0,
            'status' => CommercialInvoiceStatus::Paid->value,
        ], [[
            'concept' => 'Implementación',
            'quantity' => 1,
            'unit' => 'servicio',
            'unit_price' => 300,
            'discount' => 0,
            'tax_rate' => 0,
        ]], $user->id);

        $this->assertNotNull($invoice->paid_at);
    }

    public function test_quote_index_exposes_conversion_metrics_and_editing_action(): void
    {
        $user = User::factory()->create();
        $client = Client::query()->create(['name' => 'Cliente oportunidad', 'phone' => '8090000002']);
        $quote = CommercialQuote::query()->create([
            'client_id' => $client->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'number' => 'COT-2026-0001',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'currency' => 'USD',
            'status' => CommercialQuoteStatus::Sent,
        ]);
        $quote->items()->create(['concept' => 'Auditoría', 'quantity' => 1, 'unit' => 'servicio', 'unit_price' => 500, 'total' => 500]);

        $this->actingAs($user)->get(route('commercial.quotes.index'))
            ->assertOk()
            ->assertSee('Conversión')
            ->assertSee('Editar →')
            ->assertSee($quote->number);
    }

    public function test_paid_invoice_without_a_payment_date_remains_visible_in_paid_period_filter(): void
    {
        $user = User::factory()->create();
        $client = Client::query()->create(['name' => 'Cliente histórico', 'phone' => '8090000003']);
        $invoice = $this->invoice($client, $user, 'FAC-2026-LEGACY', CommercialInvoiceStatus::Paid, now(), now(), 100);
        $invoice->update(['paid_at' => null]);

        $this->actingAs($user)->get(route('commercial.invoices.index', [
            'date_field' => 'paid',
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ]))
            ->assertOk()
            ->assertSee($invoice->number);
    }

    private function invoice(Client $client, User $user, string $number, CommercialInvoiceStatus $status, \DateTimeInterface $issueDate, \DateTimeInterface $dueDate, float $amount): CommercialInvoice
    {
        $invoice = CommercialInvoice::query()->create([
            'client_id' => $client->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'number' => $number,
            'issue_date' => $issueDate->format('Y-m-d'),
            'due_date' => $dueDate->format('Y-m-d'),
            'currency' => 'USD',
            'status' => $status,
            'paid_at' => $status === CommercialInvoiceStatus::Paid ? $issueDate : null,
        ]);
        $invoice->items()->create(['concept' => 'Servicio', 'quantity' => 1, 'unit' => 'servicio', 'unit_price' => $amount, 'total' => $amount]);

        return $invoice;
    }
}
