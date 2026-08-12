<?php

namespace App\Services;

use App\Enums\CommitmentPaymentStatus;
use App\Models\CommitmentPayment;
use App\Models\FinancialCommitment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class CommitmentOccurrenceService
{
    /**
     * Ensure the occurrences needed by the agenda exist independently of payments.
     * Existing rows are never replaced or deleted.
     *
     * @return Collection<int, CommitmentPayment>
     */
    public function ensureForDate(FinancialCommitment $commitment, CarbonImmutable $today): Collection
    {
        $createdMonth = CarbonImmutable::instance($commitment->created_at)->startOfMonth();
        $firstMonth = $createdMonth->lessThan($today->subMonthsNoOverflow(12)->startOfMonth())
            ? $today->subMonthsNoOverflow(12)->startOfMonth()
            : $createdMonth;
        $lastMonth = $today->addMonthsNoOverflow(12)->startOfMonth();

        for ($month = $firstMonth; $month->lessThanOrEqualTo($lastMonth); $month = $month->addMonthNoOverflow()) {
            $dates = $this->datesForPeriod($commitment, $month);
            $isDueToExist = ! $commitment->has_cutoff
                || $dates['cutoff_date'] === null
                || $dates['cutoff_date']->lessThanOrEqualTo($today)
                || $dates['trigger_date']->lessThanOrEqualTo($today);

            if ($isDueToExist) {
                $existing = CommitmentPayment::query()
                    ->where('financial_commitment_id', $commitment->id)
                    ->whereDate('period_start', $month->toDateString())
                    ->first();

                if ($existing === null) {
                    CommitmentPayment::query()->create([
                        'financial_commitment_id' => $commitment->id,
                        'period_start' => $month->toDateString(),
                        'cutoff_date' => $dates['cutoff_date']?->toDateString(),
                        'due_date' => $dates['due_date']->toDateString(),
                        'expected_amount' => $commitment->suggested_amount,
                        'status' => CommitmentPaymentStatus::Pending,
                    ]);
                }
            }
        }

        return $commitment->payments()
            ->with('entries')
            ->whereBetween('period_start', [$firstMonth->toDateString(), $lastMonth->toDateString()])
            ->orderBy('period_start')
            ->get();
    }

    /** @return array{cutoff_date: ?CarbonImmutable, trigger_date: CarbonImmutable, due_date: CarbonImmutable} */
    public function datesForPeriod(FinancialCommitment $commitment, CarbonImmutable $periodStart): array
    {
        $cutoffMonth = $commitment->has_cutoff && $commitment->cutoff_day > $commitment->due_day
            ? $periodStart->subMonthNoOverflow()
            : $periodStart;

        $dueDate = $this->dateForDay($periodStart, $commitment->due_day);
        $triggerDate = $commitment->has_cutoff && $commitment->cutoff_day !== null
            ? $this->dateForDay($cutoffMonth, $commitment->cutoff_day)
            : $dueDate->subDays($commitment->activation_days_before_due ?? 15);

        return [
            'cutoff_date' => $commitment->has_cutoff
                ? $this->dateForDay($cutoffMonth, $commitment->cutoff_day)
                : null,
            'trigger_date' => $triggerDate,
            'due_date' => $dueDate,
        ];
    }

    private function dateForDay(CarbonImmutable $month, ?int $day): CarbonImmutable
    {
        return $month->setDay(min($day ?? 1, $month->daysInMonth));
    }
}
