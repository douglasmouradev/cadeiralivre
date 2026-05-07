<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Application;
use App\Core\Request;
use App\Core\Response;
use App\Exceptions\HttpException;
use App\Helpers\Csrf;

final class CsrfMiddleware implements MiddlewareInterface
{
    public function handle(Application $app, Request $request, callable $next): Response
    {
        $method = $request->method();
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            $token = $request->input('_csrf_token');
            $token = is_string($token) ? $token : null;
            if (!Csrf::validate($token)) {
                throw new HttpException('Token CSRF inválido ou expirado.', 419);
            }
        }

        return $next($request);
    }
}
