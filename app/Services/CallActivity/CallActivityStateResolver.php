<?php

declare(strict_types=1);

namespace App\Services\CallActivity;

use App\Enums\CallActivityState;
use App\Models\ContractedService;
use Carbon\CarbonImmutable;

final class CallActivityStateResolver
{
    public function resolve(ContractedService $service, ?CarbonImmutable $now = null): CallActivityState
    {
        $now ??= CarbonImmutable::now('UTC');
        $activity = $service->callActivity;

        if ($activity?->last_reported_at === null) {
            return CallActivityState::NoData;
        }

        if ($activity->last_reported_at->lt($now->subHours($service->report_delay_threshold_hours))) {
            return CallActivityState::NoReport;
        }

        if ($activity->last_outbound_at === null) {
            return CallActivityState::NoCalls;
        }

        if ($activity->last_outbound_at->lt($now->subHours($service->inactivity_threshold_hours))) {
            return CallActivityState::Inactive;
        }

        return CallActivityState::Active;
    }
}
