<?php

namespace App\Actions;

use App\Enums\CommitmentPaymentStatus;
use App\Models\CommitmentPayment;
use App\Models\FinancialCommitment;
use App\Services\CommitmentOccurrenceService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class RegisterCommitmentPayment
{
    public function __construct(
        private readonly CommitmentOccurrenceService $occurrenceService,
    ) {}

    /**
     * Legacy entry point kept for callers that identify an occurrence by period.
     * It no longer creates a future occurrence as a side effect.
     */
    public function handle(
        FinancialCommitment $commitment,
        CarbonImmutable $periodStart,
        CarbonImmutable $paidAt,
        ?string $amountPaid,
        ?string $observations,
        ?string $receiptPath,
    ): CommitmentPayment {
        $occurrence = $commitment->payments()
            ->whereDate('period_start', $periodStart->toDateString())
            ->first();

        if ($occurrence === null) {
            $dates = $this->occurrenceService->datesForPeriod($commitment, $periodStart->startOfMonth());
            $occurrence = $commitment->payments()->create([
                'period_start' => $periodStart->startOfMonth()->toDateString(),
                'cutoff_date' => $dates['cutoff_date']?->toDateString(),
                'due_date' => $dates['due_date']->toDateString(),
                'expected_amount' => $commitment->suggested_amount,
                'status' => CommitmentPaymentStatus::Pending,
            ]);
        }

        return $this->handleOccurrence($occurrence, $paidAt, $amountPaid, $observations, $receiptPath);
    }

    public function handleOccurrence(
        CommitmentPayment $occurrence,
        CarbonImmutable $paidAt,
        ?string $amountPaid,
        ?string $observations,
        ?string $receiptPath,
    ): CommitmentPayment {
        $occurrence->loadMissing(['financialCommitment', 'entries']);

        if ($occurrence->status === CommitmentPaymentStatus::Cancelled) {
            throw (new ModelNotFoundException)->setModel(CommitmentPayment::class, [$occurrence->id]);
        }

        return DB::transaction(function () use ($occurrence, $paidAt, $amountPaid, $observations, $receiptPath): CommitmentPayment {
            $expectedAmount = $occurrence->expected_amount !== null
                ? (float) $occurrence->expected_amount
                : (float) ($occurrence->financialCommitment->suggested_amount ?? 0);
            $entryAmount = $amountPaid === null ? $expectedAmount : (float) $amountPaid;
            $existingAmount = $occurrence->entries->isNotEmpty()
                ? (float) $occurrence->entries->sum('amount')
                : (float) ($occurrence->amount_paid ?? 0);
            $totalAmount = $existingAmount + $entryAmount;
            $isPaid = $expectedAmount <= 0 || $totalAmount >= $expectedAmount;

            $occurrence->entries()->create([
                'paid_at' => $paidAt->toDateString(),
                'amount' => $entryAmount,
                'observations' => $observations,
                'receipt_path' => $receiptPath,
            ]);

            $occurrence->update([
                'expected_amount' => $occurrence->expected_amount ?? ($expectedAmount > 0 ? $expectedAmount : null),
                'status' => $isPaid ? CommitmentPaymentStatus::Paid : ($totalAmount > 0 ? CommitmentPaymentStatus::PartiallyPaid : CommitmentPaymentStatus::Pending),
                'paid_at' => $isPaid ? $paidAt->toDateString() : $occurrence->paid_at,
                'amount_paid' => $totalAmount,
                'observations' => $observations ?? $occurrence->observations,
                'receipt_path' => $receiptPath ?? $occurrence->receipt_path,
            ]);

            return $occurrence->fresh(['financialCommitment', 'entries']);
        });
    }
}
