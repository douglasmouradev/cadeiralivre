<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Application;
use App\Core\Request;
use App\Core\Response;
use App\Enums\UserRole;
use App\Exceptions\UnauthorizedException;

final class SuperadminMiddleware implements MiddlewareInterface
{
    public function handle(Application $app, Request $request, callable $next): Response
    {
        $role = (string) ($_SESSION['user_role'] ?? '');
        if ($role !== UserRole::Superadmin->value) {
            throw new UnauthorizedException('Acesso reservado à equipe da plataforma.');
        }

        return $next($request);
    }
}
