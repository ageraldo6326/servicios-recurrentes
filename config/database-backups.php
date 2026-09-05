<?php

declare(strict_types=1);

return [
    'dump_binary' => env('DATABASE_BACKUP_DUMP_BINARY'),
    'process_timeout_seconds' => (int) env('DATABASE_BACKUP_TIMEOUT_SECONDS', 900),
    'lock_seconds' => (int) env('DATABASE_BACKUP_LOCK_SECONDS', 1800),
    'temporary_file_max_age_seconds' => (int) env('DATABASE_BACKUP_TEMP_MAX_AGE_SECONDS', 3600),
    'max_uncompressed_bytes' => (int) env('DATABASE_BACKUP_MAX_UNCOMPRESSED_BYTES', 0),
];
