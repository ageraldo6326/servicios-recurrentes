<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreServerCallActivityRequest;
use App\Http\Resources\ServerCallActivityReportResource;
use App\Services\CallActivity\CallActivityReporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final class ServerCallActivityController extends Controller
{
    public function __invoke(
        StoreServerCallActivityRequest $request,
        CallActivityReporter $reporter,
    ): ServerCallActivityReportResource|JsonResponse {
        $serverIp = $request->normalizedServerIp();
        $services = $reporter->monitoredServicesForIp($serverIp);

        if ($services->isEmpty()) {
            Log::notice('Call activity report rejected: server IP is unknown or monitoring is disabled.', [
                'source_ip' => $request->ip(),
                'server_ip' => $serverIp,
            ]);

            return response()->json(['ok' => false, 'message' => 'Monitored server not found.'], 404);
        }

        if ($services->count() > 1) {
            Log::warning('Call activity report rejected: server IP is ambiguous.', [
                'source_ip' => $request->ip(),
                'server_ip' => $serverIp,
                'matches' => $services->pluck('id')->all(),
            ]);

            return response()->json(['ok' => false, 'message' => 'Server IP association is ambiguous.'], 409);
        }

        $service = $services->firstOrFail();
        $result = $reporter->record($service, $request->lastOutboundAt(), $request->ip());

        return new ServerCallActivityReportResource([
            'server_id' => $service->id,
            'last_outbound_at' => $result['activity']->last_outbound_at,
            'last_reported_at' => $result['activity']->last_reported_at,
            'updated_last_outbound' => $result['updated_last_outbound'],
        ]);
    }
}
