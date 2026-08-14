<?php

return [
    'disk' => env('NOTEBOOKS_DISK', env('FILESYSTEM_DISK', 'local')),
    'max_attachment_kilobytes' => (int) env('NOTEBOOKS_MAX_ATTACHMENT_KILOBYTES', 10240),
    'version_interval_minutes' => (int) env('NOTEBOOKS_VERSION_INTERVAL_MINUTES', 5),
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'txt', 'csv', 'docx', 'xlsx', 'pptx'],
    'allowed_mimetypes' => [
        'image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf', 'text/plain', 'text/csv',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ],
];
