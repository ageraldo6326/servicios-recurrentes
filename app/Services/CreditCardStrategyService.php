<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CreditCardPurchaseEfficiency;
use App\Models\FinancialCommitment;
use Carbon\CarbonImmutable;

final class CreditCardStrategyService
{
    /** @return array<string, mixed>|null */
    public function forCommitment(FinancialCommitment $commitment, CarbonImmutable $today): ?array
    {
        if (! $commitment->isCreditCard() || $commitment->cutoff_day === null) {
            return null;
        }

        $today = $today->startOfDay();
        $previousCutoff = $this->dateForDay($today, $commitment->cutoff_day);
        if ($previousCutoff->gt($today)) {
            $previousCutoff = $this->dateForDay($today->subMonthNoOverflow(), $commitment->cutoff_day);
        }

        $nextCutoff = $this->dateForDay($previousCutoff->addMonthNoOverflow(), $commitment->cutoff_day);
        $paymentDate = $this->paymentDateForCutoff($previousCutoff, $commitment->due_day);
        $recommendedPaymentDate = $paymentDate->subDays((int) ($commitment->payment_safety_days ?? 2));
        $purchasePaymentDate = $this->paymentDateForCutoff($nextCutoff, $commitment->due_day);
        $bestPurchaseDate = $nextCutoff->addDay();
        $bestPurchasePaymentDate = $this->paymentDateForCutoff($nextCutoff->addMonthNoOverflow(), $commitment->due_day);
        $estimatedDays = (int) $today->diffInDays($purchasePaymentDate, false);
        $bestEstimatedDays = (int) $bestPurchaseDate->diffInDays($bestPurchasePaymentDate, false);
        $cutoffDays = (int) $today->diffInDays($nextCutoff, false);
        $paymentDays = (int) $today->diffInDays($paymentDate, false);
        $statementBalance = $commitment->statement_balance === null ? null : (float) $commitment->statement_balance;
        $currentBalance = $commitment->current_balance === null ? null : (float) $commitment->current_balance;
        $creditLimit = $commitment->credit_limit === null ? null : (float) $commitment->credit_limit;
        $availableCredit = $creditLimit === null || $currentBalance === null ? null : max(0, $creditLimit - $currentBalance);
        $efficiency = $this->efficiency($commitment, $today, $previousCutoff, $cutoffDays);
        $alert = $this->alert($commitment, $today, $efficiency, $cutoffDays, $paymentDays, $estimatedDays, $statementBalance, $recommendedPaymentDate, $paymentDate, $nextCutoff);

        return [
            'previous_cutoff' => $previousCutoff,
            'next_cutoff' => $nextCutoff,
            'payment_date' => $paymentDate,
            'recommended_payment_date' => $recommendedPaymentDate,
            'cutoff_days' => $cutoffDays,
            'payment_days' => $paymentDays,
            'best_purchase_date' => $bestPurchaseDate,
            'estimated_days_to_pay' => $estimatedDays,
            'best_estimated_days_to_pay' => $bestEstimatedDays,
            'days_gained_waiting' => max(0, $bestEstimatedDays - $estimatedDays),
            'efficiency' => $efficiency,
            'statement_balance' => $statementBalance,
            'current_balance' => $currentBalance,
            'available_credit' => $availableCredit,
            'utilization' => $creditLimit !== null && $creditLimit > 0 && $currentBalance !== null ? round(($currentBalance / $creditLimit) * 100) : null,
            'currency' => $commitment->card_currency ?? 'DOP',
            'alert' => $alert,
        ];
    }

    private function efficiency(FinancialCommitment $commitment, CarbonImmutable $today, CarbonImmutable $previousCutoff, int $cutoffDays): CreditCardPurchaseEfficiency
    {
        if ($today->isSameDay($previousCutoff) || $cutoffDays <= 1) {
            return CreditCardPurchaseEfficiency::Wait;
        }

        $daysAfterCutoff = (int) $previousCutoff->diffInDays($today);

        return match (true) {
            $daysAfterCutoff <= (int) ($commitment->purchase_excellent_days ?? 7) => CreditCardPurchaseEfficiency::Excellent,
            $daysAfterCutoff <= (int) ($commitment->purchase_good_days ?? 15) => CreditCardPurchaseEfficiency::Good,
            $daysAfterCutoff <= (int) ($commitment->purchase_regular_days ?? 22) => CreditCardPurchaseEfficiency::Regular,
            default => CreditCardPurchaseEfficiency::Inconvenient,
        };
    }

    /** @return array{level: string, rank: int, title: string, message: string} */
    private function alert(FinancialCommitment $commitment, CarbonImmutable $today, CreditCardPurchaseEfficiency $efficiency, int $cutoffDays, int $paymentDays, int $estimatedDays, ?float $statementBalance, CarbonImmutable $recommendedPaymentDate, CarbonImmutable $paymentDate, CarbonImmutable $nextCutoff): array
    {
        if ($statementBalance !== null && $statementBalance > 0 && $paymentDays < 0) {
            return ['level' => 'critical', 'rank' => 1, 'title' => 'Pago vencido', 'message' => 'El saldo al corte sigue pendiente desde el '.$paymentDate->format('d/m/Y').'.'];
        }
        if ($statementBalance !== null && $statementBalance > 0 && $paymentDays <= 0) {
            return ['level' => 'critical', 'rank' => 2, 'title' => 'Pagar hoy', 'message' => 'Paga el saldo al corte antes de la fecha límite bancaria.'];
        }
        if ($statementBalance !== null && $statementBalance > 0 && in_array($paymentDays, $this->alertDays($commitment->payment_alert_days), true)) {
            return ['level' => $paymentDays <= 3 ? 'high' : 'medium', 'rank' => 3, 'title' => 'Pago próximo', 'message' => 'El saldo al corte debe pagarse completo antes del '.$paymentDate->format('d/m/Y').'.'];
        }
        if ($statementBalance !== null && $statementBalance > 0 && $recommendedPaymentDate->lessThanOrEqualTo($today)) {
            return ['level' => 'high', 'rank' => 3, 'title' => 'Pago recomendado', 'message' => 'Tu fecha objetivo de pago ya llegó; prioriza el saldo al corte.'];
        }
        if ($statementBalance !== null && $statementBalance > 0) {
            return ['level' => 'medium', 'rank' => 4, 'title' => 'Saldo al corte pendiente', 'message' => 'Prioriza pagar el saldo al corte completo antes del '.$paymentDate->format('d/m/Y').'.'];
        }
        if (in_array($cutoffDays, $this->alertDays($commitment->cutoff_alert_days), true)) {
            return ['level' => $cutoffDays <= 1 ? 'high' : 'medium', 'rank' => 5, 'title' => 'Corte próximo', 'message' => 'Corta en '.$cutoffDays.' día(s). Si la compra no es urgente, espera al '.$nextCutoff->addDay()->format('d/m/Y').'.'];
        }

        return ['level' => 'info', 'rank' => 6, 'title' => $efficiency->label(), 'message' => 'Si compras hoy tendrás aproximadamente '.$estimatedDays.' días de plazo antes del pago estimado.'];
    }

    /** @return array<int, int> */
    private function alertDays(?string $days): array
    {
        return collect(explode(',', $days ?: '7,3,1'))
            ->map(fn (string $day): int => (int) trim($day))
            ->filter(fn (int $day): bool => $day >= 0 && $day <= 31)
            ->values()
            ->all();
    }

    private function paymentDateForCutoff(CarbonImmutable $cutoff, int $dueDay): CarbonImmutable
    {
        $month = $cutoff->day > $dueDay ? $cutoff->addMonthNoOverflow() : $cutoff;

        return $this->dateForDay($month, $dueDay);
    }

    private function dateForDay(CarbonImmutable $month, int $day): CarbonImmutable
    {
        return $month->startOfMonth()->setDay(min($day, $month->daysInMonth));
    }
}
