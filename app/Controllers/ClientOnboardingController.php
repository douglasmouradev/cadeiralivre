<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Helpers\Csrf;
use App\Helpers\Flash;
use App\Middleware\LoginRateLimitMiddleware;
use App\Models\ClientModel;
use App\Models\TenantModel;

final class ClientOnboardingController extends Controller
{
    private const SESSION_KEY = '_client_first_access';

    /** @return array{name: string, email: string, password_hash: string, phone: string}|null */
    private function onboardingData(): ?array
    {
        $d = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($d)) {
            return null;
        }
        $name = isset($d['name']) && is_string($d['name']) ? trim($d['name']) : '';
        $email = isset($d['email']) && is_string($d['email']) ? mb_strtolower(trim($d['email'])) : '';
        $ph = isset($d['password_hash']) && is_string($d['password_hash']) ? $d['password_hash'] : '';
        $phone = isset($d['phone']) && is_string($d['phone']) ? trim($d['phone']) : '';
        if ($name === '' || $email === '' || $ph === '') {
            return null;
        }

        return ['name' => $name, 'email' => $email, 'password_hash' => $ph, 'phone' => $phone];
    }

    private function clearOnboarding(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }

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

    public function showStep1(): Response
    {
        ClientPortalController::clearPortalSession();

        return $this->view('public/client_onboarding_step1', [
            'title' => 'Primeiro acesso — cliente',
            'csrf' => Csrf::token(),
        ]);
    }

    public function restart(): Response
    {
        $this->clearOnboarding();

        return Response::redirect('/primeiro-acesso');
    }

    public function postStep1(): Response
    {
        ClientPortalController::clearPortalSession();
        $name = trim((string) $this->request->input('name'));
        $email = mb_strtolower(trim((string) $this->request->input('email')));
        $phone = trim((string) $this->request->input('phone'));
        $password = (string) $this->request->input('password');
        if ($name === '' || $email === '' || strlen($password) < 8) {
            Flash::set('error', 'Preencha nome, e-mail e senha (mín. 8 caracteres).');

            return Response::redirect('/primeiro-acesso');
        }
        $_SESSION[self::SESSION_KEY] = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ];

        return Response::redirect('/primeiro-acesso/barbearias');
    }

    public function showStep2(): Response
    {
        if ($this->onboardingData() === null) {
            Flash::set('error', 'Comece pelo cadastro dos seus dados.');

            return Response::redirect('/primeiro-acesso');
        }
        $tenants = (new TenantModel())->listPublicDirectory();

        return $this->view('public/client_onboarding_step2', [
            'title' => 'Escolha sua barbearia',
            'csrf' => Csrf::token(),
            'tenants' => $tenants,
        ]);
    }

    public function postStep2(): Response
    {
        $data = $this->onboardingData();
        if ($data === null) {
            Flash::set('error', 'Sessão expirada. Comece novamente.');

            return Response::redirect('/primeiro-acesso');
        }
        $slug = trim((string) $this->request->input('tenant_slug'));
        if ($slug === '') {
            Flash::set('error', 'Selecione uma barbearia.');

            return Response::redirect('/primeiro-acesso/barbearias');
        }
        $tenant = $this->tenantFromSlug($slug);
        if ($tenant === null) {
            Flash::set('error', 'Barbearia não encontrada ou indisponível.');

            return Response::redirect('/primeiro-acesso/barbearias');
        }
        $tid = (int) $tenant['id'];
        $clients = new ClientModel();
        $existing = $clients->findByTenantEmail($tid, $data['email']);
        if ($existing !== null) {
            if (!empty($existing['portal_password_hash'])) {
                Flash::set('error', 'Este e-mail já tem conta nesta barbearia. Use “Entrar” na página da barbearia.');
                $this->clearOnboarding();

                return Response::redirect('/cliente/' . rawurlencode($slug) . '/entrar');
            }
            if (!$clients->activatePortal($tid, (int) $existing['id'], $data['name'], $data['phone'], $data['password_hash'])) {
                Flash::set('error', 'Não foi possível ativar a conta nesta barbearia.');

                return Response::redirect('/primeiro-acesso/barbearias');
            }
            LoginRateLimitMiddleware::clear();
            session_regenerate_id(true);
            $this->setPortalSession($tid, (int) $existing['id']);
        } else {
            $id = $clients->createWithPortal($tid, $data['name'], $data['email'], $data['phone'], $data['password_hash']);
            LoginRateLimitMiddleware::clear();
            session_regenerate_id(true);
            $this->setPortalSession($tid, $id);
        }
        $this->clearOnboarding();
        Flash::set('success', 'Conta pronta! Escolha o horário do seu atendimento.');

        return Response::redirect('/agendar/' . rawurlencode($slug));
    }
}
