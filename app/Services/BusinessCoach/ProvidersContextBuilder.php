<?php

declare(strict_types=1);

namespace App\Services\BusinessCoach;

use App\Models\Provider;

final class ProvidersContextBuilder implements ContextBuilder
{
    public function build(): array
    {
        return [
            'page' => 'proveedores',
            'total_proveedores' => Provider::query()->count(),
            'proveedores_con_servicios' => Provider::query()->whereHas('contractedServices', fn ($query) => $query->where('status', 'active'))->count(),
            'proveedores_con_pagos_parciales' => Provider::query()->where('accepts_partial_payments', true)->count(),
            'costo_mensual_activo' => (float) Provider::query()->withSum(['contractedServices as active_cost_total' => fn ($query) => $query->where('status', 'active')], 'cost')->get()->sum('active_cost_total'),
        ];
    }
}
