<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CommercialInvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\UnplannedExpenseStatus;
use App\Models\CommercialInvoice;
use App\Models\CommitmentPayment;
use App\Models\CommitmentPaymentEntry;
use App\Models\Payment;
use App\Models\UnplannedExpense;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class FinancialHistoryService
{
    /**
     * @return array{
     *     points: array<int, array<string, float|string>,
     *     totals: array{recurring: float, invoices: float, income: float, commitments: float, unplanned: float, expenses: float, net: float},
     *     unconverted_expenses_dop: float,
     *     chart: array{width: int, height: int, baseline: int}
     * }
     */
    public function report(CarbonImmutable $from, CarbonImmutable $to, ?float $expenseExchangeRate): array
    {
        $from = $from->startOfMonth();
        $to = $to->endOfMonth();
        $points = $this->emptyPoints($from, $to);
        $unconvertedExpensesDop = 0.0;

        Payment::query()
            ->where('status', PaymentStatus::Validated)
            ->whereBetween('received_at', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->each(function (Payment $payment) use (&$points): void {
                $this->addAmount($points, 'recurring', CarbonImmutable::parse($payment->received_at), (float) $payment->amount);
            });

        CommercialInvoice::query()
            ->with('items')
            ->where('status', CommercialInvoiceStatus::Paid)
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$from->startOfDay(), $to->endOfDay()])
            ->get()
            ->each(function (CommercialInvoice $invoice) use (&$points): void {
                $this->addAmount($points, 'invoices', CarbonImmutable::parse($invoice->paid_at), $invoice->total);
            });

        CommitmentPaymentEntry::query()
            ->whereBetween('paid_at', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->each(function (CommitmentPaymentEntry $entry) use (&$points, $expenseExchangeRate, &$unconvertedExpensesDop): void {
                $this->addPesoExpense($points, 'commitments', CarbonImmutable::parse($entry->paid_at), (float) $entry->amount, $expenseExchangeRate, $unconvertedExpensesDop);
            });

        CommitmentPayment::query()
            ->whereDoesntHave('entries')
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->each(function (CommitmentPayment $payment) use (&$points, $expenseExchangeRate, &$unconvertedExpensesDop): void {
                $this->addPesoExpense($points, 'commitments', CarbonImmutable::parse($payment->paid_at), (float) $payment->amount_paid, $expenseExchangeRate, $unconvertedExpensesDop);
            });

        UnplannedExpense::query()
            ->where('status', UnplannedExpenseStatus::Paid)
            ->whereNotNull('expense_date')
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->each(function (UnplannedExpense $expense) use (&$points, $expenseExchangeRate, &$unconvertedExpensesDop): void {
                $this->addPesoExpense($points, 'unplanned', CarbonImmutable::parse($expense->expense_date), (float) $expense->amount, $expenseExchangeRate, $unconvertedExpensesDop);
            });

        $points = $points->map(function (array $point): array {
            $point['income'] = $point['recurring'] + $point['invoices'];
            $point['expenses'] = $point['commitments'] + $point['unplanned'];
            $point['net'] = $point['income'] - $point['expenses'];

            return $point;
        });

        $chart = [
            'width' => max(720, $points->count() * 64),
            'height' => 250,
            'baseline' => 204,
        ];
        $scale = max(1, (float) $points->max(fn (array $point): float => max($point['income'], $point['expenses'])));
        $slotWidth = $chart['width'] / max(1, $points->count());
        $points = $points->values()->map(function (array $point, int $index) use ($scale, $slotWidth, $chart): array {
            $incomeHeight = round(($point['income'] / $scale) * 174, 2);
            $expenseHeight = round(($point['expenses'] / $scale) * 174, 2);
            $recurringHeight = $point['income'] === 0.0 ? 0.0 : round(($point['recurring'] / $point['income']) * $incomeHeight, 2);
            $invoiceHeight = $incomeHeight - $recurringHeight;
            $center = round(($index * $slotWidth) + ($slotWidth / 2), 2);

            $point['chart_income_x'] = $center - 16;
            $point['chart_expense_x'] = $center + 4;
            $point['chart_income_y'] = $chart['baseline'] - $incomeHeight;
            $point['chart_expense_y'] = $chart['baseline'] - $expenseHeight;
            $point['chart_recurring_y'] = $chart['baseline'] - $recurringHeight;
            $point['chart_invoice_y'] = $point['chart_income_y'];
            $point['chart_income_height'] = $incomeHeight;
            $point['chart_expense_height'] = $expenseHeight;
            $point['chart_recurring_height'] = $recurringHeight;
            $point['chart_invoice_height'] = $invoiceHeight;
            $point['chart_label_x'] = $center;

            return $point;
        })->all();

        return [
            'points' => $points,
            'totals' => [
                'recurring' => (float) collect($points)->sum('recurring'),
                'invoices' => (float) collect($points)->sum('invoices'),
                'income' => (float) collect($points)->sum('income'),
                'commitments' => (float) collect($points)->sum('commitments'),
                'unplanned' => (float) collect($points)->sum('unplanned'),
                'expenses' => (float) collect($points)->sum('expenses'),
                'net' => (float) collect($points)->sum('net'),
            ],
            'unconverted_expenses_dop' => $unconvertedExpensesDop,
            'chart' => $chart,
        ];
    }

    /** @return Collection<string, array<string, float|string>> */
    private function emptyPoints(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $labels = [1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'];
        $points = collect();

        for ($month = $from; $month->lte($to); $month = $month->addMonth()) {
            $points->put($month->format('Y-m'), [
                'key' => $month->format('Y-m'),
                'label' => $labels[(int) $month->month].' '.$month->format('y'),
                'recurring' => 0.0,
                'invoices' => 0.0,
                'commitments' => 0.0,
                'unplanned' => 0.0,
            ]);
        }

        return $points;
    }

    /** @param Collection<string, array<string, float|string>> $points */
    private function addAmount(Collection $points, string $source, CarbonImmutable $date, float $amount): void
    {
        $key = $date->format('Y-m');
        $point = $points->get($key);

        if ($point === null) {
            return;
        }

        $point[$source] += $amount;
        $points->put($key, $point);
    }

    /** @param Collection<string, array<string, float|string>> $points */
    private function addPesoExpense(
        Collection $points,
        string $source,
        CarbonImmutable $date,
        float $amount,
        ?float $exchangeRate,
        float &$unconvertedExpensesDop,
    ): void {
        if ($exchangeRate === null || $exchangeRate <= 0) {
            $unconvertedExpensesDop += $amount;

            return;
        }

        $this->addAmount($points, $source, $date, $amount / $exchangeRate);
    }
}
