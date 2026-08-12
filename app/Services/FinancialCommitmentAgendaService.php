<?php

namespace App\Services;

use App\Enums\CommitmentPaymentStatus;
use App\Enums\FinancialCommitmentPriority;
use App\Models\CommitmentPayment;
use App\Models\FinancialCommitment;
use Carbon\CarbonImmutable;

class FinancialCommitmentAgendaService
{
    public function __construct(
        private readonly CommitmentOccurrenceService $occurrenceService,
    ) {}

    /**
     * Keep the legacy service entry point for callers that need the current period.
     * The dashboard uses forOccurrence() so it can show several periods at once.
     *
     * @return array<string, mixed>
     */
    public function forDate(FinancialCommitment $commitment, ?CarbonImmutable $date = null): array
    {
        $today = ($date ?? CarbonImmutable::now(config('app.timezone')))->startOfDay();
        $periodStart = $today->startOfMonth();
        $dates = $this->occurrenceService->datesForPeriod($commitment, $periodStart);
        $occurrence = $commitment->payments()
            ->with('entries')
            ->whereDate('period_start', $periodStart->toDateString())
            ->first();

        if ($occurrence === null) {
            $occurrence = new CommitmentPayment([
                'financial_commitment_id' => $commitment->id,
                'period_start' => $periodStart->toDateString(),
                'cutoff_date' => $dates['cutoff_date']?->toDateString(),
                'due_date' => $dates['due_date']->toDateString(),
                'expected_amount' => $commitment->suggested_amount,
                'status' => CommitmentPaymentStatus::Pending,
            ]);
            $occurrence->setRelation('entries', collect());
        }

        return $this->forOccurrence($occurrence, $today);
    }

    /**
     * @return array{
     *     occurrence: CommitmentPayment,
     *     period_start: CarbonImmutable,
     *     cutoff_date: ?CarbonImmutable,
     *     trigger_date: CarbonImmutable,
     *     due_date: CarbonImmutable,
     *     cutoff_days: ?int,
     *     due_days: ?int,
     *     cutoff_label: ?string,
     *     due_label: string,
     *     priority: FinancialCommitmentPriority,
     *     reminder: ?string,
     *     status: CommitmentPaymentStatus,
     *     expected_amount: ?float,
     *     amount_paid: float,
     *     balance: ?float,
     *     is_paid: bool,
     *     days_paid_early: ?int,
     *     days_paid_late: ?int,
     *     payment_timing_label: ?string,
     * }
     */
    public function forOccurrence(CommitmentPayment $occurrence, CarbonImmutable $date): array
    {
        $today = $date->startOfDay();
        $commitment = $occurrence->financialCommitment;
        $periodStart = CarbonImmutable::instance($occurrence->period_start)->startOfDay();
        $cutoffDate = $occurrence->cutoff_date
            ?? $this->occurrenceService->datesForPeriod($commitment, $periodStart)['cutoff_date'];
        $dueDate = $occurrence->due_date
            ?? $this->occurrenceService->datesForPeriod($commitment, $periodStart)['due_date'];
        $triggerDate = $cutoffDate
            ?? CarbonImmutable::instance($dueDate)->subDays($commitment->activation_days_before_due ?? 15);
        $expectedAmount = $occurrence->expected_amount !== null
            ? (float) $occurrence->expected_amount
            : ($commitment->suggested_amount === null ? null : (float) $commitment->suggested_amount);
        $amountPaid = $this->amountPaid($occurrence);
        $isLegacyPaid = $occurrence->status === CommitmentPaymentStatus::Paid && $occurrence->entries->isEmpty();
        $isPaid = $occurrence->status === CommitmentPaymentStatus::Paid
            || ($expectedAmount !== null && $amountPaid >= $expectedAmount);
        $isPartiallyPaid = ! $isPaid && $amountPaid > 0 && $expectedAmount !== null && $amountPaid < $expectedAmount;
        $isOverdue = ! $isPaid && $occurrence->status !== CommitmentPaymentStatus::Cancelled && $dueDate->lt($today);
        $isProjected = ! $isPaid
            && ! $isOverdue
            && $occurrence->status !== CommitmentPaymentStatus::Cancelled
            && $today->lt($triggerDate);
        $status = match (true) {
            $occurrence->status === CommitmentPaymentStatus::Cancelled => CommitmentPaymentStatus::Cancelled,
            $isPaid => CommitmentPaymentStatus::Paid,
            $isOverdue => CommitmentPaymentStatus::Overdue,
            $isProjected => CommitmentPaymentStatus::Projected,
            $isPartiallyPaid => CommitmentPaymentStatus::PartiallyPaid,
            default => CommitmentPaymentStatus::Pending,
        };
        $dueDays = $isPaid || $status === CommitmentPaymentStatus::Cancelled
            ? null
            : (int) $today->diffInDays($dueDate, false);
        $cutoffDays = $cutoffDate === null ? null : (int) $today->diffInDays($cutoffDate, false);
        $paymentDate = $this->paymentDate($occurrence, $isLegacyPaid);
        $daysPaidEarly = $isPaid && $paymentDate?->lt($dueDate)
            ? (int) $paymentDate->diffInDays($dueDate)
            : null;
        $daysPaidLate = $isPaid && $paymentDate?->gt($dueDate)
            ? (int) $dueDate->diffInDays($paymentDate)
            : null;

        return [
            'occurrence' => $occurrence,
            'period_start' => $periodStart,
            'cutoff_date' => $cutoffDate,
            'trigger_date' => $triggerDate,
            'due_date' => $dueDate,
            'cutoff_days' => $cutoffDays,
            'due_days' => $dueDays,
            'cutoff_label' => $cutoffDate === null ? null : $this->cutoffLabel($cutoffDays),
            'due_label' => $this->dueLabel($status, $dueDays),
            'priority' => $this->priority($status, $dueDays),
            'reminder' => $this->reminder($status, $dueDays),
            'status' => $status,
            'expected_amount' => $expectedAmount,
            'amount_paid' => $amountPaid,
            'balance' => $expectedAmount === null ? null : max(0, $expectedAmount - $amountPaid),
            'is_paid' => $isPaid,
            'days_paid_early' => $daysPaidEarly,
            'days_paid_late' => $daysPaidLate,
            'payment_timing_label' => $this->paymentTimingLabel($isPaid, $daysPaidEarly, $daysPaidLate, $paymentDate),
        ];
    }

    private function amountPaid(CommitmentPayment $occurrence): float
    {
        if ($occurrence->entries->isNotEmpty()) {
            return (float) $occurrence->entries->sum('amount');
        }

        return (float) ($occurrence->amount_paid ?? 0);
    }

    private function paymentDate(CommitmentPayment $occurrence, bool $isLegacyPaid): ?CarbonImmutable
    {
        if ($occurrence->entries->isNotEmpty()) {
            $entry = $occurrence->entries->sortByDesc('paid_at')->first();

            return $entry?->paid_at === null ? null : CarbonImmutable::instance($entry->paid_at);
        }

        return $isLegacyPaid && $occurrence->paid_at !== null
            ? CarbonImmutable::instance($occurrence->paid_at)
            : null;
    }

    private function priority(CommitmentPaymentStatus $status, ?int $dueDays): FinancialCommitmentPriority
    {
        return match (true) {
            $status === CommitmentPaymentStatus::Paid => FinancialCommitmentPriority::Paid,
            $status === CommitmentPaymentStatus::Cancelled => FinancialCommitmentPriority::Paid,
            $status === CommitmentPaymentStatus::Overdue => FinancialCommitmentPriority::Critical,
            $dueDays === 0 => FinancialCommitmentPriority::High,
            $dueDays !== null && $dueDays <= 3 => FinancialCommitmentPriority::Medium,
            $dueDays !== null && $dueDays <= 7 => FinancialCommitmentPriority::Low,
            default => FinancialCommitmentPriority::Future,
        };
    }

    private function reminder(CommitmentPaymentStatus $status, ?int $dueDays): ?string
    {
        return match (true) {
            $status === CommitmentPaymentStatus::Paid => null,
            $status === CommitmentPaymentStatus::Cancelled => null,
            $status === CommitmentPaymentStatus::Overdue => 'Pago vencido',
            $dueDays === 0 => 'Recordatorio: pagar hoy',
            $dueDays === 1 => 'Recordatorio: pagar mañana',
            $dueDays === 3 => 'Recordatorio: faltan 3 días',
            $dueDays === 7 => 'Recordatorio: faltan 7 días',
            default => null,
        };
    }

    private function dueLabel(CommitmentPaymentStatus $status, ?int $dueDays): string
    {
        if ($status === CommitmentPaymentStatus::Paid || $status === CommitmentPaymentStatus::Cancelled) {
            return $status === CommitmentPaymentStatus::Cancelled ? 'Cancelado' : 'Pagado';
        }

        if ($dueDays === null) {
            return '—';
        }

        return $this->relativeLabel($dueDays, 'pago') ?? '—';
    }

    private function paymentTimingLabel(bool $isPaid, ?int $daysPaidEarly, ?int $daysPaidLate, ?CarbonImmutable $paymentDate): ?string
    {
        if (! $isPaid || $paymentDate === null) {
            return null;
        }

        if ($daysPaidEarly !== null) {
            return 'Pagado '.$daysPaidEarly.' días antes';
        }

        if ($daysPaidLate !== null) {
            return 'Pagado '.$daysPaidLate.' días después';
        }

        return 'Pagado el día del vencimiento';
    }

    private function relativeLabel(?int $days, string $event): ?string
    {
        if ($days === null) {
            return null;
        }

        return match (true) {
            $days < 0 => 'Vencido hace '.abs($days).' días',
            $days === 0 => 'Hoy',
            $days === 1 => $event === 'pago' ? 'Vence mañana' : 'Mañana',
            default => 'Faltan '.$days.' días',
        };
    }

    private function cutoffLabel(?int $days): ?string
    {
        if ($days === null) {
            return null;
        }

        return match (true) {
            $days < 0 => 'Vencido hace '.abs($days).' días',
            $days === 0 => 'Hoy',
            default => $days.' días',
        };
    }
}
