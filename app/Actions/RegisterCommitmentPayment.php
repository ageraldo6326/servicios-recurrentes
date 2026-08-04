<?php

namespace App\Actions;

use App\Enums\CommitmentPaymentStatus;
use App\Models\CommitmentPayment;
use App\Models\FinancialCommitment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class RegisterCommitmentPayment
{
    public function handle(
        FinancialCommitment $commitment,
        CarbonImmutable $periodStart,
        CarbonImmutable $paidAt,
        ?string $amountPaid,
        ?string $observations,
        ?string $receiptPath,
    ): CommitmentPayment {
        return DB::transaction(function () use ($commitment, $periodStart, $paidAt, $amountPaid, $observations, $receiptPath): CommitmentPayment {
            $payment = CommitmentPayment::query()
                ->where('financial_commitment_id', $commitment->id)
                ->whereDate('period_start', $periodStart->toDateString())
                ->first() ?? new CommitmentPayment([
                    'financial_commitment_id' => $commitment->id,
                    'period_start' => $periodStart->toDateString(),
                ]);

            $payment->fill([
                'due_date' => $this->dateForDay($periodStart, $commitment->due_day)->toDateString(),
                'status' => CommitmentPaymentStatus::Paid,
                'paid_at' => $paidAt->toDateString(),
                'amount_paid' => $amountPaid,
                'observations' => $observations,
                'receipt_path' => $receiptPath,
            ])->save();

            $nextPeriod = $periodStart->addMonthNoOverflow()->startOfMonth();
            $nextPaymentExists = CommitmentPayment::query()
                ->where('financial_commitment_id', $commitment->id)
                ->whereDate('period_start', $nextPeriod->toDateString())
                ->exists();

            if (! $nextPaymentExists) {
                CommitmentPayment::query()->create([
                    'financial_commitment_id' => $commitment->id,
                    'period_start' => $nextPeriod->toDateString(),
                    'due_date' => $this->dateForDay($nextPeriod, $commitment->due_day)->toDateString(),
                    'status' => CommitmentPaymentStatus::Pending,
                ]);
            }

            return $payment->fresh();
        });
    }

    private function dateForDay(CarbonImmutable $month, int $day): CarbonImmutable
    {
        return $month->setDay(min($day, $month->daysInMonth));
    }
}
