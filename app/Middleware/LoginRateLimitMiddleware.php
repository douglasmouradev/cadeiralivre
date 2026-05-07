<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Application;
use App\Core\Request;
use App\Core\Response;
use App\Exceptions\HttpException;

final class LoginRateLimitMiddleware implements MiddlewareInterface
{
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_SECONDS = 900;

    public function handle(Application $app, Request $request, callable $next): Response
    {
        $key = '_login_attempts';
        $now = time();
        $data = $_SESSION[$key] ?? null;
        if (!is_array($data)) {
            $data = ['count' => 0, 'reset_at' => $now + self::WINDOW_SECONDS];
        }
        $resetAt = (int) ($data['reset_at'] ?? 0);
        if ($now > $resetAt) {
            $data = ['count' => 0, 'reset_at' => $now + self::WINDOW_SECONDS];
        }
        if ((int) $data['count'] >= self::MAX_ATTEMPTS) {
            throw new HttpException('Muitas tentativas de login. Aguarde 15 minutos.', 429);
        }

        return $next($request);
    }

    public static function recordFailure(): void
    {
        $key = '_login_attempts';
        $now = time();
        $data = $_SESSION[$key] ?? ['count' => 0, 'reset_at' => $now + self::WINDOW_SECONDS];
        if (!is_array($data)) {
            $data = ['count' => 0, 'reset_at' => $now + self::WINDOW_SECONDS];
        }
        if ($now > (int) ($data['reset_at'] ?? 0)) {
            $data = ['count' => 0, 'reset_at' => $now + self::WINDOW_SECONDS];
        }
        $data['count'] = (int) $data['count'] + 1;
        $_SESSION[$key] = $data;
    }

    public static function clear(): void
    {
        unset($_SESSION['_login_attempts']);
    }
}
