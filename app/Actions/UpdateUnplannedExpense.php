<?php

namespace App\Actions;

use App\Models\UnplannedExpense;
use App\Models\UnplannedExpenseHistory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class UpdateUnplannedExpense
{
    public function execute(UnplannedExpense $expense, array $attributes, int $userId): UnplannedExpense
    {
        return DB::transaction(function () use ($expense, $attributes, $userId): UnplannedExpense {
            $expense->fill([
                ...Arr::only($attributes, [
                    'name', 'type', 'amount', 'place', 'expense_date',
                    'registered_at', 'context', 'status', 'observations',
                ]),
                'updated_by' => $userId,
            ]);
            $expense->save();

            UnplannedExpenseHistory::query()->create([
                'unplanned_expense_id' => $expense->id,
                'user_id' => $userId,
                'action' => 'updated',
                'data' => $expense->toArray(),
            ]);

            return $expense->refresh();
        });
    }
}
