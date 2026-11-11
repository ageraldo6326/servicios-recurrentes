<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CommitmentPaymentStatus;
use App\Models\FinancialCommitment;

final class SynchronizePendingCommitmentAmounts
{
    public function handle(FinancialCommitment $commitment): int
    {
        return $commitment->payments()
            ->whereNotIn('status', [
                CommitmentPaymentStatus::Paid->value,
                CommitmentPaymentStatus::Cancelled->value,
            ])
            ->update([
                'expected_amount' => $commitment->suggested_amount,
            ]);
    }
}
