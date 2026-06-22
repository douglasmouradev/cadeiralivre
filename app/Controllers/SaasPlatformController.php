<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Helpers\Csrf;
use App\Helpers\Flash;
use App\Models\TenantModel;

final class SaasPlatformController extends Controller
{
    public function tenants(): Response
    {
        $rows = (new TenantModel())->listAllForPlatform();

        return $this->view('saas/tenants', [
            'title' => 'Lojas da plataforma',
            'tenants' => $rows,
            'csrf' => Csrf::token(),
            'currentNav' => 'tenants',
        ]);
    }

    public function suspend(int $id): Response
    {
        $tenants = new TenantModel();
        if ($tenants->findById($id) === null) {
            Flash::set('error', 'Barbearia não encontrada.');

            return Response::redirect('/saas/tenants');
        }
        $tenants->setStatus($id, 'suspended');
        Flash::set('success', 'Barbearia suspensa.');

        return Response::redirect('/saas/tenants');
    }

    public function activate(int $id): Response
    {
        $tenants = new TenantModel();
        if ($tenants->findById($id) === null) {
            Flash::set('error', 'Barbearia não encontrada.');

            return Response::redirect('/saas/tenants');
        }
        $tenants->setStatus($id, 'active');
        Flash::set('success', 'Barbearia reativada.');

        return Response::redirect('/saas/tenants');
    }
}
