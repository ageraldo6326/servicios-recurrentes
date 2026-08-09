<?php

namespace App\Actions;

use App\Models\UnplannedExpense;
use App\Models\UnplannedExpenseHistory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class RegisterUnplannedExpense
{
    public function execute(array $attributes, int $userId): UnplannedExpense
    {
        return DB::transaction(function () use ($attributes, $userId): UnplannedExpense {
            $expense = UnplannedExpense::query()->create([
                ...Arr::only($attributes, [
                    'name', 'type', 'amount', 'place', 'expense_date',
                    'registered_at', 'context', 'status', 'observations',
                ]),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            UnplannedExpenseHistory::query()->create([
                'unplanned_expense_id' => $expense->id,
                'user_id' => $userId,
                'action' => 'created',
                'data' => $expense->toArray(),
            ]);

            return $expense;
        });
    }
}
