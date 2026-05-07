<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Application;
use App\Core\Request;
use App\Core\Response;
use App\Enums\UserRole;
use App\Exceptions\UnauthorizedException;

/** Apenas dono da barbearia ou superadmin (criar nova barbearia / rotas restritas). */
final class OwnerMiddleware implements MiddlewareInterface
{
    public function handle(Application $app, Request $request, callable $next): Response
    {
        $role = (string) ($_SESSION['user_role'] ?? '');
        if (!in_array($role, [UserRole::Owner->value, UserRole::Superadmin->value], true)) {
            throw new UnauthorizedException('Apenas o dono pode aceder a esta área.');
        }

        return $next($request);
    }
}
