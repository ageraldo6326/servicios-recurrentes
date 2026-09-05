<?php

declare(strict_types=1);

namespace App\Services\DatabaseBackups;

use App\Models\DatabaseBackupRun;

final readonly class DatabaseBackupResult
{
    public function __construct(
        public DatabaseBackupRun $run,
        public string $temporaryPath,
        public string $fileName,
    ) {}
}
