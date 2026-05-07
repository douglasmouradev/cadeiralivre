<?php

declare(strict_types=1);

return [
    'name' => $_ENV['APP_NAME'] ?? 'CadeiraLivre',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL),
    'url' => rtrim($_ENV['APP_URL'] ?? '', '/'),
    'session_lifetime' => (int) ($_ENV['SESSION_LIFETIME'] ?? 120),
    'upload_max_bytes' => (int) ($_ENV['UPLOAD_MAX_BYTES'] ?? 2_097_152),
];
