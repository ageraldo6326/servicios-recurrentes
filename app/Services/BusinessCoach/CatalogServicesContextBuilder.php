<?php

declare(strict_types=1);

namespace App\Services\BusinessCoach;

use App\Models\CatalogService;

final class CatalogServicesContextBuilder implements ContextBuilder
{
    public function build(): array
    {
        return [
            'page' => 'servicios',
            'servicios_catalogo' => CatalogService::query()->count(),
            'servicios_activos' => CatalogService::query()->where('is_active', true)->count(),
            'servicios_sin_contrataciones' => CatalogService::query()->doesntHave('contractedServices')->count(),
        ];
    }
}
