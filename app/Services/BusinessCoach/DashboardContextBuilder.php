<?php

declare(strict_types=1);

namespace App\Services\BusinessCoach;

use App\Models\Charge;
use App\Models\ContractedService;
use App\Models\ProviderInvoice;
use Carbon\CarbonImmutable;

final class DashboardContextBuilder implements ContextBuilder
{
    public function build(): array
    {
        $today = CarbonImmutable::today();
        $end = $today->endOfMonth();

        return [
            'page' => 'dashboard',
            'periodo' => [$today->toDateString(), $end->toDateString()],
            'servicios_activos' => ContractedService::query()->where('status', 'active')->count(),
            'cobros_pendientes_periodo' => (float) Charge::query()->whereBetween('due_date', [$today, $end])->where('status', '!=', 'paid')->sum('amount'),
            'cobros_atrasados' => Charge::query()->whereDate('due_date', '<', $today)->where('status', '!=', 'paid')->count(),
            'facturas_proveedor_pendientes' => (float) ProviderInvoice::query()->whereBetween('due_date', [$today, $end])->where('status', '!=', 'paid')->sum('amount'),
        ];
    }
}
