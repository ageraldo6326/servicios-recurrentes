<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ServerCallActivityReport;
use Illuminate\Console\Command;

final class PruneServerCallActivityReports extends Command
{
    protected $signature = 'call-activity:prune-reports';

    protected $description = 'Remove diagnostic server call activity reports older than the configured retention period';

    public function handle(): int
    {
        $retentionDays = max(1, (int) config('services.call_activity.report_retention_days', 90));
        $deleted = ServerCallActivityReport::query()
            ->where('received_at', '<', now('UTC')->subDays($retentionDays))
            ->delete();

        $this->info("Deleted {$deleted} expired call activity reports.");

        return self::SUCCESS;
    }
}
