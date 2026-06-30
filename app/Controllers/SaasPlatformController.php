<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Response;
use App\Enums\UserRole;
use App\Helpers\Csrf;
use App\Helpers\Flash;
use App\Helpers\Str;
use App\Models\PlanDefinitionModel;
use App\Models\SaasAuditModel;
use App\Models\SaasPlatformModel;
use App\Models\BarberModel;
use App\Models\TenantModel;
use App\Models\UserModel;
use App\Services\TenantTemplateService;

final class SaasPlatformController extends Controller
{
    public function dashboard(): Response
    {
        $platform = new SaasPlatformModel();
        $stats = $platform->platformStats();
        $trialsExpiring = $platform->trialsExpiringSoon(7);
        $pastDue = $platform->listTenantsFiltered(null, 'active', 'past_due', 'created_desc');
        $churnRisk = $platform->inactiveTenants(30);
        $recentLogs = (new SaasAuditModel())->recent(15);

        return $this->view('saas/dashboard', [
            'title' => 'Visão geral',
            'stats' => $stats,
            'trialsExpiring' => $trialsExpiring,
            'pastDueTenants' => $pastDue,
            'churnRisk' => $churnRisk,
            'recentLogs' => $recentLogs,
            'currentNav' => 'dashboard',
        ]);
    }

    public function tenants(): Response
    {
        $q = $this->request->query();
        $search = isset($q['q']) ? (string) $q['q'] : '';
        $status = isset($q['status']) ? (string) $q['status'] : 'all';
        $sub = isset($q['sub']) ? (string) $q['sub'] : 'all';
        $sort = isset($q['sort']) ? (string) $q['sort'] : 'created_desc';

        $rows = (new SaasPlatformModel())->listTenantsFiltered(
            $search !== '' ? $search : null,
            $status !== 'all' ? $status : null,
            $sub !== 'all' ? $sub : null,
            $sort,
        );

        return $this->view('saas/tenants', [
            'title' => 'Lojas',
            'tenants' => $rows,
            'filters' => [
                'q' => $search,
                'status' => $status,
                'sub' => $sub,
                'sort' => $sort,
            ],
            'currentNav' => 'tenants',
        ]);
    }

    public function exportTenants(): Response
    {
        $rows = (new SaasPlatformModel())->listTenantsFiltered(null, null, null, 'created_desc');
        $lines = ["id;nome;slug;email;cidade;estado;status;plano;assinatura;criada_em"];
        foreach ($rows as $t) {
            $city = trim((string) ($t['city'] ?? '') . ' ' . (string) ($t['state'] ?? ''));
            $lines[] = implode(';', [
                (int) ($t['id'] ?? 0),
                str_replace(';', ',', (string) ($t['name'] ?? '')),
                (string) ($t['slug'] ?? ''),
                (string) ($t['email'] ?? ''),
                str_replace(';', ',', (string) ($t['city'] ?? '')),
                (string) ($t['state'] ?? ''),
                (string) ($t['status'] ?? ''),
                str_replace(';', ',', (string) ($t['plan_label'] ?? $t['plan'] ?? '')),
                subscription_status_label((string) ($t['subscription_status'] ?? 'none')),
                (string) ($t['created_at'] ?? ''),
            ]);
        }
        $csv = implode("\n", $lines) . "\n";
        $filename = 'lojas-' . date('Y-m-d') . '.csv';

        return Response::csv($filename, $csv);
    }

    public function tenantShow(int $id): Response
    {
        $tenants = new TenantModel();
        $tenant = $tenants->findById($id);
        if ($tenant === null) {
            Flash::set('error', 'Loja não encontrada.');

            return Response::redirect('/saas/tenants');
        }

        $planRow = null;
        $planId = $tenant['plan_definition_id'] ?? null;
        if ($planId !== null && (int) $planId > 0) {
            $planRow = (new PlanDefinitionModel())->findById((int) $planId);
        }

        $platform = new SaasPlatformModel();
        $stats = $platform->tenantDetailStats($id);
        $owner = (new UserModel())->findOwnerByTenant($id);
        $plans = (new PlanDefinitionModel())->all();
        $auditLogs = (new SaasAuditModel())->recent(20, $id);
        $base = app_base_url();
        $slug = (string) ($tenant['slug'] ?? '');

        return $this->view('saas/tenant_show', [
            'title' => (string) ($tenant['name'] ?? 'Loja'),
            'tenant' => $tenant,
            'plan' => $planRow,
            'stats' => $stats,
            'owner' => $owner,
            'plans' => $plans,
            'auditLogs' => $auditLogs,
            'publicUrl' => $slug !== '' ? $base . '/agendar/' . rawurlencode($slug) : '',
            'currentNav' => 'tenants',
        ]);
    }

    public function suspend(int $id): Response
    {
        $tenants = new TenantModel();
        $tenant = $tenants->findById($id);
        if ($tenant === null) {
            Flash::set('error', 'Loja não encontrada.');

            return Response::redirect('/saas/tenants');
        }
        $tenants->setStatus($id, 'suspended');
        (new SaasAuditModel())->log($this->userId(), 'tenant_suspend', $id, [
            'name' => (string) ($tenant['name'] ?? ''),
        ]);
        Flash::set('success', 'Loja suspensa.');

        return Response::redirect('/saas/tenants/' . $id);
    }

    public function activate(int $id): Response
    {
        $tenants = new TenantModel();
        $tenant = $tenants->findById($id);
        if ($tenant === null) {
            Flash::set('error', 'Loja não encontrada.');

            return Response::redirect('/saas/tenants');
        }
        $newStatus = (string) ($tenant['subscription_status'] ?? '') === 'trialing' ? 'trial' : 'active';
        $tenants->setStatus($id, $newStatus);
        (new SaasAuditModel())->log($this->userId(), 'tenant_activate', $id, [
            'name' => (string) ($tenant['name'] ?? ''),
        ]);
        Flash::set('success', 'Loja reativada.');

        return Response::redirect('/saas/tenants/' . $id);
    }

    public function updateTenantPlan(int $id): Response
    {
        $tenants = new TenantModel();
        $tenant = $tenants->findById($id);
        if ($tenant === null) {
            Flash::set('error', 'Loja não encontrada.');

            return Response::redirect('/saas/tenants');
        }

        $planId = filter_var($this->request->input('plan_definition_id'), FILTER_VALIDATE_INT);
        $subStatus = trim((string) $this->request->input('subscription_status'));
        $allowedSubs = ['none', 'trialing', 'active', 'past_due', 'canceled'];
        $fields = [];
        if ($planId !== false && $planId > 0) {
            $plan = (new PlanDefinitionModel())->findById((int) $planId);
            if ($plan !== null) {
                $fields['plan_definition_id'] = (int) $planId;
                $fields['plan'] = (string) $plan['slug'];
            }
        }
        if (in_array($subStatus, $allowedSubs, true)) {
            $fields['subscription_status'] = $subStatus;
        }
        if ($fields !== []) {
            $tenants->updateBilling($id, $fields);
            (new SaasAuditModel())->log($this->userId(), 'tenant_plan_update', $id, $fields);
            Flash::set('success', 'Plano e assinatura atualizados.');
        }

        return Response::redirect('/saas/tenants/' . $id);
    }

    public function impersonate(int $id): Response
    {
        $tenant = (new TenantModel())->findById($id);
        if ($tenant === null) {
            Flash::set('error', 'Loja não encontrada.');

            return Response::redirect('/saas/tenants');
        }

        $_SESSION['saas_impersonating'] = true;
        $_SESSION['saas_impersonate_tenant_id'] = $id;
        $_SESSION['tenant_id'] = $id;
        (new SaasAuditModel())->log($this->userId(), 'impersonate_start', $id, [
            'name' => (string) ($tenant['name'] ?? ''),
        ]);
        Flash::set('success', 'A aceder ao painel de ' . (string) ($tenant['name'] ?? 'loja') . '.');

        return Response::redirect('/painel');
    }

    public function stopImpersonate(): Response
    {
        $tenantId = isset($_SESSION['saas_impersonate_tenant_id']) ? (int) $_SESSION['saas_impersonate_tenant_id'] : 0;
        if ($tenantId > 0) {
            (new SaasAuditModel())->log($this->userId(), 'impersonate_stop', $tenantId);
        }
        unset($_SESSION['saas_impersonating'], $_SESSION['saas_impersonate_tenant_id'], $_SESSION['tenant_id']);
        Flash::set('success', 'Voltou ao painel da plataforma.');

        return Response::redirect($tenantId > 0 ? '/saas/tenants/' . $tenantId : '/saas');
    }

    public function plans(): Response
    {
        $plans = (new PlanDefinitionModel())->all();
        $counts = [];
        foreach ($plans as $p) {
            $counts[(int) $p['id']] = (new PlanDefinitionModel())->countTenantsOnPlan((int) $p['id']);
        }

        return $this->view('saas/plans', [
            'title' => 'Planos',
            'plans' => $plans,
            'tenantCounts' => $counts,
            'currentNav' => 'plans',
        ]);
    }

    public function updatePlan(int $id): Response
    {
        $plans = new PlanDefinitionModel();
        $plan = $plans->findById($id);
        if ($plan === null) {
            Flash::set('error', 'Plano não encontrado.');

            return Response::redirect('/saas/planos');
        }

        $name = trim((string) $this->request->input('name'));
        $maxBarbers = $this->request->input('max_barbers');
        $maxAppts = $this->request->input('max_appointments_per_month');
        $priceCents = filter_var($this->request->input('monthly_price_cents'), FILTER_VALIDATE_INT);
        $stripePrice = trim((string) $this->request->input('stripe_price_id'));
        $sortOrder = filter_var($this->request->input('sort_order'), FILTER_VALIDATE_INT);

        $fields = [];
        if ($name !== '') {
            $fields['name'] = $name;
        }
        if ($maxBarbers === '' || $maxBarbers === null) {
            $fields['max_barbers'] = null;
        } elseif (filter_var($maxBarbers, FILTER_VALIDATE_INT) !== false) {
            $fields['max_barbers'] = (int) $maxBarbers;
        }
        if ($maxAppts === '' || $maxAppts === null) {
            $fields['max_appointments_per_month'] = null;
        } elseif (filter_var($maxAppts, FILTER_VALIDATE_INT) !== false) {
            $fields['max_appointments_per_month'] = (int) $maxAppts;
        }
        if ($priceCents !== false && $priceCents >= 0) {
            $fields['monthly_price_cents'] = $priceCents;
        }
        $fields['stripe_price_id'] = $stripePrice !== '' ? $stripePrice : null;
        if ($sortOrder !== false) {
            $fields['sort_order'] = $sortOrder;
        }

        $plans->update($id, $fields);
        (new SaasAuditModel())->log($this->userId(), 'plan_update', null, [
            'plan_id' => $id,
            'slug' => (string) ($plan['slug'] ?? ''),
        ]);
        Flash::set('success', 'Plano atualizado.');

        return Response::redirect('/saas/planos');
    }

    public function showCreateTenant(): Response
    {
        return $this->view('saas/new_tenant', [
            'title' => 'Nova loja',
            'templates' => TenantTemplateService::slugs(),
            'currentNav' => 'new_tenant',
        ]);
    }

    public function createTenant(): Response
    {
        $name = trim((string) $this->request->input('shop_name'));
        $slug = Str::slug(trim((string) $this->request->input('slug')));
        $email = mb_strtolower(trim((string) $this->request->input('email')));
        $phone = trim((string) $this->request->input('phone'));
        $owner = trim((string) $this->request->input('owner_name'));
        $password = (string) $this->request->input('password');
        $template = trim((string) $this->request->input('template'));

        if ($name === '' || $slug === '' || $email === '' || $owner === '') {
            Flash::set('error', 'Preencha todos os campos obrigatórios.');

            return Response::redirect('/saas/loja/nova');
        }
        if (strlen($password) < 8) {
            Flash::set('error', 'A senha deve ter no mínimo 8 caracteres.');

            return Response::redirect('/saas/loja/nova');
        }

        $tenants = new TenantModel();
        if ($tenants->findBySlug($slug) !== null) {
            Flash::set('error', 'Este identificador (slug) já está em uso.');

            return Response::redirect('/saas/loja/nova');
        }
        $users = new UserModel();
        if ($users->findByEmail($email) !== null) {
            Flash::set('error', 'Este e-mail já está cadastrado.');

            return Response::redirect('/saas/loja/nova');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $tenantId = $tenants->create([
                'name' => $name,
                'slug' => $slug,
                'email' => $email,
                'phone' => $phone !== '' ? $phone : null,
                'address' => null,
                'city' => null,
                'state' => null,
                'timezone' => 'America/Sao_Paulo',
            ]);
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $users->create(
                $tenantId,
                $owner,
                $email,
                $hash,
                UserRole::Owner->value,
                $phone !== '' ? $phone : null,
            );
            if ($template !== '' && $template !== 'empty' && in_array($template, TenantTemplateService::slugs(), true)) {
                $proEmail = 'pro+' . $slug . '@' . preg_replace('/^.*@/', '', $email);
                if ($users->findByEmail($proEmail) === null) {
                    $proUid = $users->create(
                        $tenantId,
                        $owner,
                        $proEmail,
                        $hash,
                        UserRole::Barber->value,
                        $phone !== '' ? $phone : null,
                    );
                    $barberId = (new BarberModel())->create($tenantId, $proUid, [
                        'bio' => '',
                        'specialties' => [],
                        'commission_percent' => 100.0,
                        'is_available' => true,
                    ]);
                    (new TenantTemplateService())->apply($tenantId, $barberId, $template);
                }
            }
            $pdo->commit();
        } catch (\Throwable) {
            $pdo->rollBack();
            Flash::set('error', 'Não foi possível criar a loja. Tente novamente.');

            return Response::redirect('/saas/loja/nova');
        }

        (new SaasAuditModel())->log($this->userId(), 'tenant_create', $tenantId, [
            'name' => $name,
            'slug' => $slug,
            'template' => $template,
        ]);
        Flash::set('success', 'Loja criada com sucesso.');

        return Response::redirect('/saas/tenants/' . $tenantId);
    }

    public function settings(): Response
    {
        $stripeConfigured = trim((string) ($_ENV['STRIPE_SECRET_KEY'] ?? '')) !== '';
        $mailHost = trim((string) ($_ENV['MAIL_SMTP_HOST'] ?? ''));

        return $this->view('saas/settings', [
            'title' => 'Configurações',
            'appName' => app_name(),
            'appUrl' => app_base_url(),
            'appEnv' => (string) ($_ENV['APP_ENV'] ?? 'production'),
            'stripeConfigured' => $stripeConfigured,
            'mailConfigured' => mail_configured() && $mailHost !== '',
            'currentNav' => 'settings',
        ]);
    }
}
