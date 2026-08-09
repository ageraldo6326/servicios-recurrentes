<?php

namespace App\Actions;

use App\Models\UnplannedExpense;
use App\Models\UnplannedExpenseHistory;
use Illuminate\Support\Facades\DB;

final class DeleteUnplannedExpense
{
    public function execute(UnplannedExpense $expense, int $userId): void
    {
        DB::transaction(function () use ($expense, $userId): void {
            $expense->update(['updated_by' => $userId]);

            UnplannedExpenseHistory::query()->create([
                'unplanned_expense_id' => $expense->id,
                'user_id' => $userId,
                'action' => 'deleted',
                'data' => $expense->fresh()->toArray(),
            ]);

            $expense->delete();
        });
    }
}
