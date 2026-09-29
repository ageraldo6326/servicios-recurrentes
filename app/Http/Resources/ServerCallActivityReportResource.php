<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ServerCallActivityReportResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'ok' => true,
            'server_id' => $this->resource['server_id'],
            'last_outbound_at' => $this->resource['last_outbound_at']?->utc()->toIso8601ZuluString(),
            'last_reported_at' => $this->resource['last_reported_at']->utc()->toIso8601ZuluString(),
            'updated_last_outbound' => $this->resource['updated_last_outbound'],
        ];
    }
}
