<?php

declare(strict_types=1);

namespace App\Services\BusinessCoach;

use App\Models\CommitmentPayment;
use App\Models\FinancialCommitment;
use Carbon\CarbonImmutable;

final class FinancialAgendaContextBuilder implements ContextBuilder
{
    public function build(): array
    {
        $today = CarbonImmutable::today();

        return [
            'page' => 'compromisos',
            'compromisos_activos' => FinancialCommitment::query()->where('is_active', true)->count(),
            'monto_sugerido_activo' => (float) FinancialCommitment::query()->where('is_active', true)->sum('suggested_amount'),
            'pagos_pendientes_periodo' => CommitmentPayment::query()->where('status', 'pending')->whereMonth('period_start', $today->month)->whereYear('period_start', $today->year)->count(),
            'pagos_vencidos' => CommitmentPayment::query()->where('status', 'pending')->whereDate('due_date', '<', $today)->count(),
        ];
    }
}
