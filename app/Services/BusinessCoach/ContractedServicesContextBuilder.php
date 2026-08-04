<?php

declare(strict_types=1);

namespace App\Services\BusinessCoach;

use App\Models\ContractedService;

final class ContractedServicesContextBuilder implements ContextBuilder
{
    public function build(): array
    {
        return [
            'page' => 'servicios_contratados',
            'servicios_activos' => ContractedService::query()->where('status', 'active')->count(),
            'ingreso_mensual' => (float) ContractedService::query()->where('status', 'active')->sum('price'),
            'costo_mensual' => (float) ContractedService::query()->where('status', 'active')->sum('cost'),
            'servicios_cancelados' => ContractedService::query()->where('status', 'cancelled')->count(),
        ];
    }
}
