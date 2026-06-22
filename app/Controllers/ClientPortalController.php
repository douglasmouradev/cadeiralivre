<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Helpers\Csrf;
use App\Helpers\Flash;
use App\Middleware\LoginRateLimitMiddleware;
use App\Models\ClientModel;
use App\Models\TenantModel;

final class ClientPortalController extends Controller
{
    private function tenantFromSlug(string $slug): ?array
    {
        $t = (new TenantModel())->findBySlug($slug);
        if ($t === null || (string) $t['status'] === 'suspended') {
            return null;
        }

        return $t;
    }

    private function setPortalSession(int $tenantId, int $clientId): void
    {
        $_SESSION['portal_tenant_id'] = $tenantId;
        $_SESSION['portal_client_id'] = $clientId;
    }

    public static function clearPortalSession(): void
    {
        unset($_SESSION['portal_tenant_id'], $_SESSION['portal_client_id']);
    }

    public function showLogin(string $slug): Response
    {
        $tenant = $this->tenantFromSlug($slug);
        if ($tenant === null) {
            return Response::html('<!DOCTYPE html><html><body><p>Barbearia não encontrada.</p></body></html>', 404);
        }

        return $this->view('public/client_portal_login', [
            'title' => 'Entrar como cliente — ' . $tenant['name'],
            'tenant' => $tenant,
            'slug' => $slug,
            'csrf' => Csrf::token(),
        ]);
    }

    public function login(string $slug): Response
    {
        $tenant = $this->tenantFromSlug($slug);
        if ($tenant === null) {
            return Response::redirect('/');
        }
        $tid = (int) $tenant['id'];
        $email = mb_strtolower(trim((string) $this->request->input('email')));
        $password = (string) $this->request->input('password');
        $clients = new ClientModel();
        $row = $clients->findByTenantEmail($tid, $email);
        if ($row === null || empty($row['portal_password_hash']) || !password_verify($password, (string) $row['portal_password_hash'])) {
            LoginRateLimitMiddleware::recordFailure();
            Flash::set('error', 'E-mail ou senha incorretos.');

            return Response::redirect('/cliente/' . rawurlencode($slug) . '/entrar');
        }
        LoginRateLimitMiddleware::clear();
        session_regenerate_id(true);
        $this->setPortalSession($tid, (int) $row['id']);
        Flash::set('success', 'Bem-vindo! Escolha o horário do seu próximo atendimento.');

        return Response::redirect('/agendar/' . rawurlencode($slug));
    }

    public function showRegister(string $slug): Response
    {
        $tenant = $this->tenantFromSlug($slug);
        if ($tenant === null) {
            return Response::html('<!DOCTYPE html><html><body><p>Barbearia não encontrada.</p></body></html>', 404);
        }

        return $this->view('public/client_portal_register', [
            'title' => 'Criar conta de cliente — ' . $tenant['name'],
            'tenant' => $tenant,
            'slug' => $slug,
            'csrf' => Csrf::token(),
        ]);
    }

    public function register(string $slug): Response
    {
        $tenant = $this->tenantFromSlug($slug);
        if ($tenant === null) {
            return Response::redirect('/');
        }
        $tid = (int) $tenant['id'];
        $name = trim((string) $this->request->input('name'));
        $email = mb_strtolower(trim((string) $this->request->input('email')));
        $phone = trim((string) $this->request->input('phone'));
        $password = (string) $this->request->input('password');
        if ($name === '' || $email === '' || strlen($password) < 8) {
            Flash::set('error', 'Preencha nome, e-mail e senha (mín. 8 caracteres).');

            return Response::redirect('/cliente/' . rawurlencode($slug) . '/cadastro');
        }
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $clients = new ClientModel();
        $existing = $clients->findByTenantEmail($tid, $email);
        if ($existing !== null) {
            if (!empty($existing['portal_password_hash'])) {
                Flash::set('error', 'Este e-mail já possui conta. Use “Entrar”.');

                return Response::redirect('/cliente/' . rawurlencode($slug) . '/entrar');
            }
            if (!$clients->activatePortal($tid, (int) $existing['id'], $name, $phone, $hash)) {
                Flash::set('error', 'Não foi possível ativar a conta. Tente novamente.');

                return Response::redirect('/cliente/' . rawurlencode($slug) . '/cadastro');
            }
            session_regenerate_id(true);
            $this->setPortalSession($tid, (int) $existing['id']);
        } else {
            $id = $clients->createWithPortal($tid, $name, $email, $phone, $hash);
            session_regenerate_id(true);
            $this->setPortalSession($tid, $id);
        }
        Flash::set('success', 'Conta criada! Escolha o horário do seu atendimento.');

        return Response::redirect('/agendar/' . rawurlencode($slug));
    }

    public function logout(string $slug): Response
    {
        self::clearPortalSession();
        Flash::set('success', 'Você saiu da conta de cliente.');

        return Response::redirect('/agendar/' . rawurlencode($slug));
    }
}
