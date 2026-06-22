<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Application;
use App\Core\Request;
use App\Core\Response;
use App\Enums\UserRole;
use App\Models\TenantModel;

abstract class Controller
{
    public function __construct(
        protected readonly Application $app,
        protected readonly Request $request,
    ) {
    }

    /** @param array<string, mixed> $data */
    protected function view(string $template, array $data = [], int $status = 200): Response
    {
        $path = $this->app->root() . '/app/Views/' . $template . '.php';
        if (!is_file($path)) {
            throw new \RuntimeException('View não encontrada: ' . $template);
        }
        if (!array_key_exists('user_role', $data)) {
            $data['user_role'] = (string) ($_SESSION['user_role'] ?? '');
        }
        if (!array_key_exists('csrf', $data)) {
            $data['csrf'] = \App\Helpers\Csrf::token();
        }
        if (!array_key_exists('admin_tenant', $data)) {
            $tid = $_SESSION['tenant_id'] ?? null;
            if ($tid !== null && $tid !== '') {
                $data['admin_tenant'] = (new TenantModel())->findById((int) $tid);
            } else {
                $data['admin_tenant'] = null;
            }
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $path;
        $html = (string) ob_get_clean();

        return Response::html($html, $status);
    }

    /** @param array<string, mixed> $data */
    protected function partial(string $template, array $data = []): string
    {
        $path = $this->app->root() . '/app/Views/' . $template . '.php';
        if (!is_file($path)) {
            throw new \RuntimeException('Partial não encontrado: ' . $template);
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $path;

        return (string) ob_get_clean();
    }

    protected function tenantId(): int
    {
        $tid = $_SESSION['tenant_id'] ?? null;
        if ($tid === null || $tid === '') {
            throw new \RuntimeException('Tenant não definido na sessão.');
        }

        return (int) $tid;
    }

    protected function userId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    protected function userRole(): string
    {
        return (string) ($_SESSION['user_role'] ?? '');
    }

    /** Rota inicial após login (barbeiro → agenda; demais → painel). */
    protected function postAuthHomePath(): string
    {
        if ($this->userRole() === UserRole::Superadmin->value) {
            return '/saas/tenants';
        }

        return $this->userRole() === UserRole::Barber->value ? '/agenda' : '/painel';
    }
}
