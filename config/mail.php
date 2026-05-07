<?php

declare(strict_types=1);

return [
    'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@example.com',
    'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'CadeiraLivre',
    'smtp_host' => $_ENV['MAIL_SMTP_HOST'] ?? 'localhost',
    'smtp_port' => (int) ($_ENV['MAIL_SMTP_PORT'] ?? 587),
    'smtp_user' => $_ENV['MAIL_SMTP_USER'] ?? '',
    'smtp_pass' => $_ENV['MAIL_SMTP_PASS'] ?? '',
    'smtp_encryption' => $_ENV['MAIL_SMTP_ENCRYPTION'] ?? 'tls',
];
