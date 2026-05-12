<?php

declare(strict_types=1);

/**
 * Router para o servidor embutido do PHP (desenvolvimento).
 * Uso: php -S localhost:8000 -t public public/router.php
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$path = __DIR__ . $uri;
if ($uri !== '/' && $uri !== '' && is_file($path)) {
    return false;
}

require __DIR__ . '/index.php';
