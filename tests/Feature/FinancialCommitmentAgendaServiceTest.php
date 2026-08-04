<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CommitmentPaymentStatus;
use App\Enums\FinancialCommitmentPriority;
use App\Models\Beneficiary;
use App\Models\CommitmentPayment;
use App\Models\FinancialCommitment;
use App\Services\FinancialCommitmentAgendaService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialCommitmentAgendaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_an_overdue_payment_and_reminder(): void
    {
        $commitment = $this->commitment(5, null);

        $agenda = app(FinancialCommitmentAgendaService::class)->forDate(
            $commitment,
            CarbonImmutable::parse('2026-08-10')
        );

        $this->assertSame(FinancialCommitmentPriority::Critical, $agenda['priority']);
        $this->assertSame(-5, $agenda['due_days']);
        $this->assertSame('Vencido hace 5 días', $agenda['due_label']);
        $this->assertSame('Pago vencido', $agenda['reminder']);
    }

    public function test_it_calculates_today_and_configured_reminder_windows(): void
    {
        $service = app(FinancialCommitmentAgendaService::class);

        $today = $service->forDate($this->commitment(15, null), CarbonImmutable::parse('2026-08-15'));
        $threeDays = $service->forDate($this->commitment(18, null), CarbonImmutable::parse('2026-08-15'));
        $sevenDays = $service->forDate($this->commitment(22, null), CarbonImmutable::parse('2026-08-15'));

        $this->assertSame(FinancialCommitmentPriority::High, $today['priority']);
        $this->assertSame('Hoy', $today['due_label']);
        $this->assertSame('Recordatorio: pagar hoy', $today['reminder']);
        $this->assertSame('Recordatorio: faltan 3 días', $threeDays['reminder']);
        $this->assertSame('Recordatorio: faltan 7 días', $sevenDays['reminder']);
    }

    public function test_payment_due_date_has_priority_over_an_upcoming_cutoff_event(): void
    {
        $agenda = app(FinancialCommitmentAgendaService::class)->forDate(
            $this->commitment(28, 20),
            CarbonImmutable::parse('2026-08-15')
        );

        $this->assertSame(FinancialCommitmentPriority::Future, $agenda['priority']);
        $this->assertSame(5, $agenda['cutoff_days']);
        $this->assertSame('5 días', $agenda['cutoff_label']);
        $this->assertSame(13, $agenda['due_days']);
    }

    public function test_it_clamps_monthly_dates_to_the_last_day_of_the_month(): void
    {
        $agenda = app(FinancialCommitmentAgendaService::class)->forDate(
            $this->commitment(31, 31),
            CarbonImmutable::parse('2026-02-01')
        );

        $this->assertSame('2026-02-28', $agenda['due_date']->toDateString());
        $this->assertSame('2026-02-28', $agenda['cutoff_date']->toDateString());
    }

    public function test_cutoff_after_payment_day_belongs_to_the_previous_month(): void
    {
        $agenda = app(FinancialCommitmentAgendaService::class)->forDate(
            $this->commitment(5, 25),
            CarbonImmutable::parse('2026-08-01')
        );

        $this->assertSame('2026-07-25', $agenda['cutoff_date']->toDateString());
        $this->assertSame('2026-08-05', $agenda['due_date']->toDateString());
    }

    public function test_a_paid_period_has_no_reminder_and_is_sorted_last(): void
    {
        $commitment = $this->commitment(5, null);
        CommitmentPayment::query()->create([
            'financial_commitment_id' => $commitment->id,
            'period_start' => '2026-08-01',
            'due_date' => '2026-08-05',
            'status' => CommitmentPaymentStatus::Paid,
            'paid_at' => '2026-08-04',
        ]);

        $agenda = app(FinancialCommitmentAgendaService::class)->forDate(
            $commitment->fresh(),
            CarbonImmutable::parse('2026-08-10')
        );

        $this->assertTrue($agenda['is_paid']);
        $this->assertSame(FinancialCommitmentPriority::Paid, $agenda['priority']);
        $this->assertNull($agenda['reminder']);
    }

    private function commitment(int $dueDay, ?int $cutoffDay): FinancialCommitment
    {
        $beneficiary = Beneficiary::query()->create([
            'name' => 'Beneficiario '.$dueDay,
            'type' => 'Servicio',
        ]);

        return FinancialCommitment::query()->create([
            'beneficiary_id' => $beneficiary->id,
            'name' => 'Compromiso '.$dueDay,
            'category' => 'General',
            'frequency' => 'monthly',
            'has_cutoff' => $cutoffDay !== null,
            'cutoff_day' => $cutoffDay,
            'due_day' => $dueDay,
            'is_active' => true,
        ]);
    }
}
