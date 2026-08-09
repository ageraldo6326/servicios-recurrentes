<?php

namespace App\Services;

use App\Enums\UnplannedExpenseStatus;
use App\Models\UnplannedExpense;
use Carbon\CarbonImmutable;

final class UnplannedExpenseDashboardService
{
    /** @return array<string, mixed> */
    public function summary(CarbonImmutable $today): array
    {
        $monthStart = $today->startOfMonth()->toDateString();
        $monthEnd = $today->endOfMonth()->toDateString();
        $previousStart = $today->subMonth()->startOfMonth()->toDateString();
        $previousEnd = $today->subMonth()->endOfMonth()->toDateString();

        $monthExpenses = UnplannedExpense::query()->between($monthStart, $monthEnd);
        $total = (float) (clone $monthExpenses)->sum('amount');
        $paid = (float) (clone $monthExpenses)->where('status', UnplannedExpenseStatus::Paid->value)->sum('amount');
        $pending = (float) (clone $monthExpenses)->where('status', UnplannedExpenseStatus::Pending->value)->sum('amount');
        $previousTotal = (float) UnplannedExpense::query()->between($previousStart, $previousEnd)->sum('amount');

        $byType = (clone $monthExpenses)
            ->selectRaw("COALESCE(NULLIF(type, ''), 'Sin clasificar') as label, SUM(amount) as total")
            ->groupBy('type')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $lastExpense = UnplannedExpense::query()->latest('expense_date')->latest('id')->first();
        $yesterday = $today->subDay()->toDateString();

        return [
            'total' => $total,
            'paid' => $paid,
            'pending' => $pending,
            'previousTotal' => $previousTotal,
            'byType' => $byType,
            'lastExpense' => $lastExpense,
            'needsReminder' => ! UnplannedExpense::query()->whereDate('expense_date', $yesterday)->exists(),
            'hasAlert' => $previousTotal > 0 && $total > $previousTotal,
        ];
    }
}
