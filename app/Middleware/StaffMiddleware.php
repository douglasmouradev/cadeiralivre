<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Application;
use App\Core\Request;
use App\Core\Response;
use App\Enums\UserRole;
use App\Exceptions\UnauthorizedException;

final class StaffMiddleware implements MiddlewareInterface
{
    public function handle(Application $app, Request $request, callable $next): Response
    {
        $role = $_SESSION['user_role'] ?? '';
        $allowed = [
            UserRole::Owner->value,
            UserRole::Receptionist->value,
            UserRole::Barber->value,
            UserRole::Superadmin->value,
        ];
        if (!in_array($role, $allowed, true)) {
            throw new UnauthorizedException('Sem permissão para acessar a agenda.');
        }

        return $next($request);
    }
}
