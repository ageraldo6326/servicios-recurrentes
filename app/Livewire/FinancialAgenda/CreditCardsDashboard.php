<?php

declare(strict_types=1);

namespace App\Livewire\FinancialAgenda;

use App\Models\FinancialCommitment;
use App\Services\CreditCardStrategyService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class CreditCardsDashboard extends Component
{
    public function render(CreditCardStrategyService $strategyService): View
    {
        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();
        $cards = FinancialCommitment::query()
            ->with('beneficiary')
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->where('is_credit_card', true)
                    ->orWhere('category', 'like', '%tarjeta%');
            })
            ->orderBy('name')
            ->get()
            ->map(function (FinancialCommitment $commitment) use ($strategyService, $today): ?array {
                $strategy = $strategyService->forCommitment($commitment, $today);

                return $strategy === null ? null : ['commitment' => $commitment, 'strategy' => $strategy];
            })
            ->filter()
            ->sortBy(fn (array $card): int => $card['strategy']['alert']['rank'])
            ->values();

        return view('livewire.financial-agenda.credit-cards-dashboard', [
            'cards' => $cards,
            'summary' => [
                'total' => $cards->count(),
                'urgent' => $cards->filter(fn (array $card): bool => in_array($card['strategy']['alert']['level'], ['critical', 'high'], true))->count(),
                'excellent' => $cards->filter(fn (array $card): bool => in_array($card['strategy']['efficiency']->value, ['excellent', 'good'], true))->count(),
                'configuration' => $cards->filter(fn (array $card): bool => $card['commitment']->credit_limit === null || $card['commitment']->current_balance === null || $card['commitment']->statement_balance === null)->count(),
            ],
        ]);
    }
}
