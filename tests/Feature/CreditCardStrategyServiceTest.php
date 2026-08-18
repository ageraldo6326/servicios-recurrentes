<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CreditCardPurchaseEfficiency;
use App\Livewire\FinancialAgenda\Dashboard;
use App\Models\Beneficiary;
use App\Models\FinancialCommitment;
use App\Models\User;
use App\Services\CreditCardStrategyService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreditCardStrategyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_the_purchase_window_and_days_gained_by_waiting(): void
    {
        $strategy = app(CreditCardStrategyService::class)->forCommitment(
            $this->card(cutoffDay: 2, dueDay: 24),
            CarbonImmutable::parse('2026-08-18'),
        );

        $this->assertSame('2026-08-02', $strategy['previous_cutoff']->toDateString());
        $this->assertSame('2026-09-02', $strategy['next_cutoff']->toDateString());
        $this->assertSame('2026-08-24', $strategy['payment_date']->toDateString());
        $this->assertSame('2026-08-22', $strategy['recommended_payment_date']->toDateString());
        $this->assertSame(CreditCardPurchaseEfficiency::Regular, $strategy['efficiency']);
        $this->assertSame(37, $strategy['estimated_days_to_pay']);
        $this->assertSame(14, $strategy['days_gained_waiting']);
    }

    public function test_it_prioritizes_a_statement_balance_when_payment_is_due(): void
    {
        $strategy = app(CreditCardStrategyService::class)->forCommitment(
            $this->card(cutoffDay: 2, dueDay: 24, statementBalance: 18000),
            CarbonImmutable::parse('2026-08-22'),
        );

        $this->assertSame('high', $strategy['alert']['level']);
        $this->assertSame('Pago recomendado', $strategy['alert']['title']);
        $this->assertSame(18000.0, $strategy['statement_balance']);
    }

    public function test_existing_tarjeta_category_is_detected_without_replacing_its_data(): void
    {
        $card = $this->card(cutoffDay: 10, dueDay: 3, creditCard: false);

        $this->assertTrue($card->isCreditCard());
        $this->assertNotNull(app(CreditCardStrategyService::class)->forCommitment($card, CarbonImmutable::parse('2026-08-18')));
    }

    public function test_dashboard_shows_the_credit_card_alerts_section(): void
    {
        $user = User::factory()->create();
        $this->card(cutoffDay: 2, dueDay: 24);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSee('Alertas financieras')
            ->assertSee('Estrategia de tarjetas');
    }

    private function card(int $cutoffDay, int $dueDay, ?float $statementBalance = null, bool $creditCard = true): FinancialCommitment
    {
        $beneficiary = Beneficiary::query()->create(['name' => 'Banco de tarjetas', 'type' => 'Banco']);

        return FinancialCommitment::query()->create([
            'beneficiary_id' => $beneficiary->id,
            'name' => 'BHD Platinum',
            'category' => 'Tarjeta',
            'frequency' => 'monthly',
            'has_cutoff' => true,
            'is_credit_card' => $creditCard,
            'cutoff_day' => $cutoffDay,
            'due_day' => $dueDay,
            'payment_safety_days' => 2,
            'credit_limit' => 100000,
            'current_balance' => 82000,
            'statement_balance' => $statementBalance,
            'card_currency' => 'DOP',
            'is_active' => true,
        ]);
    }
}
