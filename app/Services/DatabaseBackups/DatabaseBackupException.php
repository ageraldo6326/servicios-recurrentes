<?php

declare(strict_types=1);

namespace App\Services\DatabaseBackups;

use RuntimeException;

class DatabaseBackupException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        public readonly string $publicMessage,
        string $technicalMessage,
    ) {
        parent::__construct($technicalMessage);
    }
}
