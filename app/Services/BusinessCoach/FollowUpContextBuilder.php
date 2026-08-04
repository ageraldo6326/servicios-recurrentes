<?php

declare(strict_types=1);

namespace App\Services\BusinessCoach;

use App\Models\Gestion;
use Carbon\CarbonImmutable;

final class FollowUpContextBuilder implements ContextBuilder
{
    public function build(): array
    {
        $today = CarbonImmutable::today();

        return [
            'page' => 'gestiones_y_seguimiento',
            'gestiones_hoy' => Gestion::query()->whereDate('occurred_at', $today)->count(),
            'seguimientos_para_hoy' => Gestion::query()->whereDate('next_follow_up_at', $today)->count(),
            'promesas_para_hoy' => Gestion::query()->whereDate('promised_payment_date', $today)->count(),
            'seguimientos_pendientes' => Gestion::query()->whereDate('next_follow_up_at', '<=', $today)->count(),
        ];
    }
}
