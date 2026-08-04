<?php

declare(strict_types=1);

namespace App\Services\BusinessCoach;

use App\Models\Client;
use Carbon\CarbonImmutable;

final class ClientsContextBuilder implements ContextBuilder
{
    public function build(): array
    {
        $monthStart = CarbonImmutable::now()->startOfMonth();

        return [
            'page' => 'clientes',
            'total_clientes' => Client::query()->count(),
            'clientes_con_servicio_activo' => Client::query()->whereHas('contractedServices', fn ($query) => $query->where('status', 'active'))->count(),
            'clientes_nuevos_mes' => Client::query()->where('created_at', '>=', $monthStart)->count(),
            'clientes_sin_servicio' => Client::query()->doesntHave('contractedServices')->count(),
        ];
    }
}
