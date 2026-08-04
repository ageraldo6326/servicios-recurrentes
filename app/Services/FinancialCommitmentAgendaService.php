<?php

namespace App\Services;

use App\Enums\CommitmentPaymentStatus;
use App\Enums\FinancialCommitmentPriority;
use App\Models\CommitmentPayment;
use App\Models\FinancialCommitment;
use Carbon\CarbonImmutable;

class FinancialCommitmentAgendaService
{
    /**
     * Calculate the current monthly agenda entry for a commitment.
     *
     * @return array{
     *     period_start: CarbonImmutable,
     *     cutoff_date: ?CarbonImmutable,
     *     due_date: CarbonImmutable,
     *     cutoff_days: ?int,
     *     due_days: int,
     *     cutoff_label: ?string,
     *     due_label: string,
     *     priority: FinancialCommitmentPriority,
     *     reminder: ?string,
     *     is_paid: bool,
     * }
     */
    public function forDate(FinancialCommitment $commitment, ?CarbonImmutable $date = null): array
    {
        $today = ($date ?? CarbonImmutable::now(config('app.timezone')))->startOfDay();
        $periodStart = $today->startOfMonth();
        $cutoffMonth = $commitment->has_cutoff && $commitment->cutoff_day > $commitment->due_day
            ? $periodStart->subMonthNoOverflow()
            : $periodStart;
        $cutoffDate = $commitment->has_cutoff
            ? $this->dateForDay($cutoffMonth, $commitment->cutoff_day)
            : null;
        $dueDate = $this->dateForDay($periodStart, $commitment->due_day);
        $payment = $this->paymentForPeriod($commitment, $periodStart);
        $isPaid = $payment?->status === CommitmentPaymentStatus::Paid;
        $dueDays = (int) $today->diffInDays($dueDate, false);
        $cutoffDays = $cutoffDate === null ? null : (int) $today->diffInDays($cutoffDate, false);

        return [
            'period_start' => $periodStart,
            'cutoff_date' => $cutoffDate,
            'due_date' => $dueDate,
            'cutoff_days' => $cutoffDays,
            'due_days' => $dueDays,
            'cutoff_label' => $cutoffDate === null ? null : $this->relativeLabel($cutoffDays, 'corte'),
            'due_label' => $this->relativeLabel($dueDays, 'pago'),
            'priority' => $this->priority($dueDays, $isPaid),
            'reminder' => $this->reminder($dueDays, $isPaid),
            'is_paid' => $isPaid,
        ];
    }

    private function dateForDay(CarbonImmutable $month, ?int $day): CarbonImmutable
    {
        $lastDay = $month->daysInMonth;

        return $month->setDay(min($day ?? 1, $lastDay));
    }

    private function paymentForPeriod(FinancialCommitment $commitment, CarbonImmutable $periodStart): ?CommitmentPayment
    {
        if ($commitment->relationLoaded('payments')) {
            return $commitment->payments->first(
                fn (CommitmentPayment $payment): bool => $payment->period_start?->isSameDay($periodStart)
            );
        }

        return $commitment->payments()->whereDate('period_start', $periodStart->toDateString())->first();
    }

    private function priority(int $dueDays, bool $isPaid): FinancialCommitmentPriority
    {
        if ($isPaid) {
            return FinancialCommitmentPriority::Paid;
        }

        if ($dueDays < 0) {
            return FinancialCommitmentPriority::Critical;
        }

        if ($dueDays === 0) {
            return FinancialCommitmentPriority::High;
        }

        if ($dueDays <= 3) {
            return FinancialCommitmentPriority::Medium;
        }

        if ($dueDays <= 7) {
            return FinancialCommitmentPriority::Low;
        }

        return FinancialCommitmentPriority::Future;
    }

    private function reminder(int $dueDays, bool $isPaid): ?string
    {
        if ($isPaid) {
            return null;
        }

        return match (true) {
            $dueDays < 0 => 'Pago vencido',
            $dueDays === 0 => 'Recordatorio: pagar hoy',
            $dueDays === 1 => 'Recordatorio: pagar mañana',
            $dueDays === 3 => 'Recordatorio: faltan 3 días',
            $dueDays === 7 => 'Recordatorio: faltan 7 días',
            default => null,
        };
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
            default => $days.' días',
        };
    }
}
