<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\ExchangeRate;
use App\Services\FinancialHistoryService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
final class CashFlow extends Component
{
    private const MINIMUM_FILTER_YEAR = 2000;

    #[Url]
    public int $months = 12;

    #[Url]
    public ?int $month = null;

    #[Url]
    public ?int $year = null;

    public function mount(): void
    {
        $this->normalizeFilters();
    }

    public function updatedMonths(): void
    {
        if (! in_array($this->months, [6, 12, 24, 36], true)) {
            $this->months = 12;
        }
    }

    public function updatedMonth(): void
    {
        $this->normalizeFilters();
    }

    public function updatedYear(): void
    {
        $this->normalizeFilters();
    }

    public function resetDateFilter(): void
    {
        $this->reset(['month', 'year']);
    }

    public function render(FinancialHistoryService $history): View
    {
        [$from, $to] = $this->selectedRange();
        $currentExchangeRate = ExchangeRate::query()
            ->whereDate('effective_date', '<=', CarbonImmutable::now(config('app.timezone'))->toDateString())
            ->latest('effective_date')
            ->latest('id')
            ->first();

        return view('livewire.dashboard.cash-flow', [
            'report' => $history->report($from, $to, $currentExchangeRate === null ? null : (float) $currentExchangeRate->rate),
            'from' => $from,
            'to' => $to,
            'currentExchangeRate' => $currentExchangeRate,
            'monthOptions' => [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'],
            'years' => range((int) CarbonImmutable::now(config('app.timezone'))->year, self::MINIMUM_FILTER_YEAR),
        ]);
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    private function selectedRange(): array
    {
        $now = CarbonImmutable::now(config('app.timezone'));

        if ($this->year === null) {
            $months = in_array($this->months, [6, 12, 24, 36], true) ? $this->months : 12;
            $to = $now->endOfMonth();

            return [$to->startOfMonth()->subMonths($months - 1), $to];
        }

        $from = CarbonImmutable::create($this->year, $this->month ?? 1, 1, 0, 0, 0, config('app.timezone'))->startOfMonth();

        return [$from, $this->month === null ? $from->endOfYear() : $from->endOfMonth()];
    }

    private function normalizeFilters(): void
    {
        $currentYear = (int) CarbonImmutable::now(config('app.timezone'))->year;

        if ($this->year !== null && ($this->year < self::MINIMUM_FILTER_YEAR || $this->year > $currentYear)) {
            $this->year = null;
        }

        if ($this->month !== null && ($this->month < 1 || $this->month > 12)) {
            $this->month = null;
        }

        if ($this->year === null) {
            $this->month = null;
        }
    }
}
