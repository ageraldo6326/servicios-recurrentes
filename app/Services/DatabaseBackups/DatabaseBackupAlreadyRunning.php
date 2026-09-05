<?php

declare(strict_types=1);

namespace App\Services\DatabaseBackups;

final class DatabaseBackupAlreadyRunning extends DatabaseBackupException
{
    public function __construct()
    {
        parent::__construct(
            'already_running',
            'Ya existe un respaldo en proceso. Espere a que termine antes de iniciar otro.',
            'A database backup lock is already held.',
        );
    }
}
