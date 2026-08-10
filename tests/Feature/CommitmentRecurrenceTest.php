<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\RegisterCommitmentPayment;
use App\Enums\CommitmentPaymentStatus;
use App\Models\Beneficiary;
use App\Models\CommitmentPayment;
use App\Models\FinancialCommitment;
use App\Services\CommitmentOccurrenceService;
use App\Services\FinancialCommitmentAgendaService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommitmentRecurrenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_before_due_date_is_paid_early_and_not_overdue(): void
    {
        $commitment = $this->commitment(10, null, 12000, '2026-08-01');
        $occurrence = app(CommitmentOccurrenceService::class)->ensureForDate($commitment, CarbonImmutable::parse('2026-08-01'))
            ->firstWhere(fn (CommitmentPayment $payment): bool => $payment->period_start->isSameDay(CarbonImmutable::parse('2026-08-01')));

        app(RegisterCommitmentPayment::class)->handleOccurrence($occurrence, CarbonImmutable::parse('2026-08-03'), '12000', null, null);

        $agenda = app(FinancialCommitmentAgendaService::class)->forOccurrence($occurrence->fresh(['financialCommitment', 'entries']), CarbonImmutable::parse('2026-08-10'));

        $this->assertSame(CommitmentPaymentStatus::Paid, $agenda['status']);
        $this->assertSame(7, $agenda['days_paid_early']);
        $this->assertNull($agenda['due_days']);
        $this->assertSame('Pagado 7 días antes', $agenda['payment_timing_label']);
    }

    public function test_payment_on_due_date_is_paid_on_time(): void
    {
        $commitment = $this->commitment(10, null, 12000, '2026-08-01');
        $occurrence = app(CommitmentOccurrenceService::class)->ensureForDate($commitment, CarbonImmutable::parse('2026-08-01'))->first();

        app(RegisterCommitmentPayment::class)->handleOccurrence($occurrence, CarbonImmutable::parse('2026-08-10'), '12000', null, null);

        $agenda = app(FinancialCommitmentAgendaService::class)->forOccurrence($occurrence->fresh(['financialCommitment', 'entries']), CarbonImmutable::parse('2026-08-10'));

        $this->assertSame(CommitmentPaymentStatus::Paid, $agenda['status']);
        $this->assertSame('Pagado el día del vencimiento', $agenda['payment_timing_label']);
        $this->assertNull($agenda['due_days']);
    }

    public function test_payment_after_due_date_is_paid_late_but_not_currently_overdue(): void
    {
        $commitment = $this->commitment(10, null, 12000, '2026-08-01');
        $occurrence = app(CommitmentOccurrenceService::class)->ensureForDate($commitment, CarbonImmutable::parse('2026-08-01'))->first();

        app(RegisterCommitmentPayment::class)->handleOccurrence($occurrence, CarbonImmutable::parse('2026-08-13'), '12000', null, null);

        $agenda = app(FinancialCommitmentAgendaService::class)->forOccurrence($occurrence->fresh(['financialCommitment', 'entries']), CarbonImmutable::parse('2026-08-13'));

        $this->assertSame(CommitmentPaymentStatus::Paid, $agenda['status']);
        $this->assertSame(3, $agenda['days_paid_late']);
        $this->assertNull($agenda['due_days']);
    }

    public function test_unpaid_occurrence_is_overdue(): void
    {
        $agenda = app(FinancialCommitmentAgendaService::class)->forDate(
            $this->commitment(10, null, 12000, '2026-08-01'),
            CarbonImmutable::parse('2026-08-13'),
        );

        $this->assertSame(CommitmentPaymentStatus::Overdue, $agenda['status']);
        $this->assertSame(-3, $agenda['due_days']);
        $this->assertSame('Vencido hace 3 días', $agenda['due_label']);
    }

    public function test_cutoff_generates_next_obligation_on_cutoff_date_without_previous_payment(): void
    {
        $commitment = $this->commitment(3, 15, 20000, '2026-08-01');
        $service = app(CommitmentOccurrenceService::class);

        $beforeCutoff = $service->ensureForDate($commitment, CarbonImmutable::parse('2026-08-14'));
        $this->assertFalse($beforeCutoff->contains(fn (CommitmentPayment $payment): bool => $payment->period_start->isSameDay(CarbonImmutable::parse('2026-09-01'))));

        $onCutoff = $service->ensureForDate($commitment, CarbonImmutable::parse('2026-08-15'));
        $next = $onCutoff->firstWhere(fn (CommitmentPayment $payment): bool => $payment->period_start->isSameDay(CarbonImmutable::parse('2026-09-01')));

        $this->assertNotNull($next);
        $this->assertSame('2026-08-15', $next->cutoff_date->toDateString());
        $this->assertSame('2026-09-03', $next->due_date->toDateString());
        $this->assertSame(CommitmentPaymentStatus::Pending, $next->status);
    }

    public function test_unpaid_months_accumulate_and_generation_is_idempotent(): void
    {
        $commitment = $this->commitment(27, null, 17000, '2026-07-01');
        $service = app(CommitmentOccurrenceService::class);

        $first = $service->ensureForDate($commitment, CarbonImmutable::parse('2026-09-10'));
        $second = $service->ensureForDate($commitment, CarbonImmutable::parse('2026-09-10'));

        $months = $second->filter(fn (CommitmentPayment $payment): bool => in_array($payment->period_start->toDateString(), ['2026-07-01', '2026-08-01', '2026-09-01'], true));

        $this->assertCount(3, $months);
        $this->assertSame($first->count(), $second->count());
        $this->assertSame(1, CommitmentPayment::query()->where('financial_commitment_id', $commitment->id)->whereDate('period_start', '2026-08-01')->count());
    }

    public function test_partial_payments_keep_balance_until_fully_paid(): void
    {
        $commitment = $this->commitment(10, null, 12000, '2026-08-01');
        $occurrence = app(CommitmentOccurrenceService::class)->ensureForDate($commitment, CarbonImmutable::parse('2026-08-01'))->first();
        $action = app(RegisterCommitmentPayment::class);

        $action->handleOccurrence($occurrence, CarbonImmutable::parse('2026-08-05'), '5000', null, null);
        $partial = app(FinancialCommitmentAgendaService::class)->forOccurrence($occurrence->fresh(['financialCommitment', 'entries']), CarbonImmutable::parse('2026-08-05'));

        $this->assertSame(CommitmentPaymentStatus::PartiallyPaid, $partial['status']);
        $this->assertSame(7000.0, $partial['balance']);

        $action->handleOccurrence($occurrence->fresh(['financialCommitment', 'entries']), CarbonImmutable::parse('2026-08-08'), '7000', null, null);
        $paid = app(FinancialCommitmentAgendaService::class)->forOccurrence($occurrence->fresh(['financialCommitment', 'entries']), CarbonImmutable::parse('2026-08-08'));

        $this->assertSame(CommitmentPaymentStatus::Paid, $paid['status']);
        $this->assertEquals(0.0, $paid['balance']);
        $this->assertCount(2, $occurrence->fresh('entries')->entries);
    }

    private function commitment(int $dueDay, ?int $cutoffDay, ?int $amount, string $createdAt): FinancialCommitment
    {
        $beneficiary = Beneficiary::query()->create(['name' => 'Beneficiario de prueba', 'type' => 'Banco']);
        $commitment = FinancialCommitment::query()->create([
            'beneficiary_id' => $beneficiary->id,
            'name' => 'Compromiso recurrente de prueba',
            'category' => $cutoffDay === null ? 'Prestamo' : 'Tarjeta',
            'frequency' => 'monthly',
            'suggested_amount' => $amount,
            'has_cutoff' => $cutoffDay !== null,
            'cutoff_day' => $cutoffDay,
            'due_day' => $dueDay,
            'is_active' => true,
        ]);
        $commitment->created_at = CarbonImmutable::parse($createdAt);
        $commitment->saveQuietly();

        return $commitment->fresh();
    }
}
