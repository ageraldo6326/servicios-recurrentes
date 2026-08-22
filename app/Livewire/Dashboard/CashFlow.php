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
    #[Url]
    public int $months = 12;

    public function updatedMonths(): void
    {
        if (! in_array($this->months, [6, 12, 24, 36], true)) {
            $this->months = 12;
        }
    }

    public function render(FinancialHistoryService $history): View
    {
        $months = in_array($this->months, [6, 12, 24, 36], true) ? $this->months : 12;
        $to = CarbonImmutable::now(config('app.timezone'))->endOfMonth();
        $from = $to->startOfMonth()->subMonths($months - 1);
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
        ]);
    }
}
