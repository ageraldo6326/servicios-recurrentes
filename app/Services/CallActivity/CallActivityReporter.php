<?php

declare(strict_types=1);

namespace App\Services\CallActivity;

use App\Enums\ContractedServiceStatus;
use App\Models\ContractedService;
use App\Models\ServerCallActivity;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class CallActivityReporter
{
    /**
     * @return Collection<int, ContractedService>
     */
    public function monitoredServicesForIp(string $serverIp): Collection
    {
        return ContractedService::query()
            ->where('status', ContractedServiceStatus::Active->value)
            ->where('call_monitoring_enabled', true)
            ->where('ip', $serverIp)
            ->get()
            ->values();
    }

    /**
     * @return array{activity: ServerCallActivity, updated_last_outbound: bool, received_at: CarbonImmutable}
     */
    public function record(ContractedService $service, ?CarbonImmutable $lastOutboundAt, ?string $sourceIp): array
    {
        return DB::transaction(function () use ($service, $lastOutboundAt, $sourceIp): array {
            $receivedAt = CarbonImmutable::now('UTC');
            ContractedService::query()->whereKey($service->id)->lockForUpdate()->firstOrFail();
            $activity = ServerCallActivity::query()
                ->where('contracted_service_id', $service->id)
                ->lockForUpdate()
                ->first();

            if ($activity === null) {
                $activity = new ServerCallActivity(['contracted_service_id' => $service->id]);
            }

            $updatedLastOutbound = $lastOutboundAt !== null
                && ($activity->last_outbound_at === null || $lastOutboundAt->gt($activity->last_outbound_at));

            if ($updatedLastOutbound) {
                $activity->last_outbound_at = $lastOutboundAt;
            }

            $activity->last_reported_at = $receivedAt;
            $activity->save();

            $service->callActivityReports()->create([
                'received_at' => $receivedAt,
                'reported_last_outbound_at' => $lastOutboundAt,
                'updated_last_outbound' => $updatedLastOutbound,
                'source_ip' => $sourceIp,
            ]);

            return [
                'activity' => $activity->fresh(),
                'updated_last_outbound' => $updatedLastOutbound,
                'received_at' => $receivedAt,
            ];
        });
    }
}
