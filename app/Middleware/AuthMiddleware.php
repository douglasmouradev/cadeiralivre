<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Application;
use App\Core\Request;
use App\Core\Response;
use App\Enums\UserRole;
use App\Models\UserModel;

final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Application $app, Request $request, callable $next): Response
    {
        $userIdRaw = $_SESSION['user_id'] ?? null;
        $id = filter_var($userIdRaw, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            return Response::redirect('/login');
        }
        $userModel = new UserModel();
        $user = $userModel->findById($id);
        if ($user === null || !(bool) $user['is_active']) {
            unset($_SESSION['user_id'], $_SESSION['tenant_id'], $_SESSION['user_role']);

            return Response::redirect('/login');
        }

        $_SESSION['tenant_id'] = $user['tenant_id'] !== null ? (int) $user['tenant_id'] : null;
        $_SESSION['user_role'] = (string) $user['role'];
        $_SESSION['user_name'] = (string) $user['name'];
        $_SESSION['user_email'] = (string) $user['email'];

        if ((string) $user['role'] === UserRole::Superadmin->value) {
            $path = $request->path();
            $allowedPrefixes = ['/saas', '/logout', '/registrar', '/esqueci-senha', '/redefinir-senha'];
            $allowed = false;
            foreach ($allowedPrefixes as $prefix) {
                if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                    $allowed = true;
                    break;
                }
            }
            if (!$allowed) {
                return Response::redirect('/saas/tenants');
            }
        }

        return $next($request);
    }
}
