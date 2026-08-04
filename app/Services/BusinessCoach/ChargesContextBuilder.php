<?php

declare(strict_types=1);

namespace App\Services\BusinessCoach;

use App\Models\Charge;
use App\Models\Payment;
use Carbon\CarbonImmutable;

final class ChargesContextBuilder implements ContextBuilder
{
    public function build(): array
    {
        $today = CarbonImmutable::today();

        return [
            'page' => 'cobros_y_pagos',
            'cobros_pendientes' => Charge::query()->where('status', '!=', 'paid')->count(),
            'monto_pendiente' => (float) Charge::query()->where('status', '!=', 'paid')->sum('amount'),
            'cobros_atrasados' => Charge::query()->whereDate('due_date', '<', $today)->where('status', '!=', 'paid')->count(),
            'pagos_por_validar' => Payment::query()->where('status', 'pending')->count(),
            'pagos_recibidos_mes' => (float) Payment::query()->whereMonth('received_at', $today->month)->whereYear('received_at', $today->year)->sum('amount'),
        ];
    }
}
