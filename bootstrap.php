<?php

declare(strict_types=1);

$root = __DIR__;

require $root . '/vendor/autoload.php';

require $root . '/config/load_env.php';
app_load_dotenv($root);

$appEnv = $_ENV['APP_ENV'] ?? 'production';
$appDebug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL);

if ($appEnv === 'local' && $appDebug) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
}

// Nginx / PHP-FPM: HTTPS nem sempre vem em $_SERVER['HTTPS']; sem isto o cookie de sessão pode falhar em HTTPS.
$forwardedProto = isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
    ? strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO'])
    : '';
if (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || $forwardedProto === 'https'
    || (isset($_SERVER['REQUEST_SCHEME']) && strtolower((string) $_SERVER['REQUEST_SCHEME']) === 'https')
) {
    $_SERVER['HTTPS'] = 'on';
}

$sessionDir = $root . '/storage/sessions';
if (!is_dir($sessionDir)) {
    @mkdir($sessionDir, 0770, true);
}
if (is_dir($sessionDir) && is_writable($sessionDir)) {
    session_save_path($sessionDir);
}

ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}

session_name($_ENV['SESSION_NAME'] ?? 'cadeira_livre_session');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Sao_Paulo');

if (empty($_SESSION['user_id'] ?? null) && isset($_COOKIE['remember']) && is_string($_COOKIE['remember'])) {
    $plain = $_COOKIE['remember'];
    if (preg_match('/^[a-f0-9]{64}$/', $plain) === 1) {
        try {
            $sm = new \App\Models\SessionModel();
            $hash = hash('sha256', $plain);
            $row = $sm->findValidByTokenHash($hash);
            if ($row !== null && isset($row['user_id'])) {
                $_SESSION['user_id'] = (int) $row['user_id'];
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_regenerate_id(true);
                }
            }
        } catch (\Throwable) {
        }
    }
}
