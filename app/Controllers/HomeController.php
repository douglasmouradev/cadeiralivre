<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Models\PlanDefinitionModel;

final class HomeController extends Controller
{
    public function index(): Response
    {
        if (!empty($_SESSION['user_id'])) {
            return Response::redirect($this->postAuthHomePath());
        }

        $plans = (new PlanDefinitionModel())->all();

        $activeTenants = 0;
        try {
            $activeTenants = (int) \App\Core\Database::connection()
                ->query("SELECT COUNT(*) FROM tenants WHERE status <> 'suspended'")
                ->fetchColumn();
        } catch (\Throwable) {
            $activeTenants = 0;
        }

        return $this->view('home/landing', [
            'title' => app_name() . ' — Agendamento online para seu negócio',
            'plans' => $plans,
            'demoBookingUrl' => '/agendar/adriele-cardoso-nail-design',
            'baseUrl' => app_base_url(),
            'activeTenants' => $activeTenants,
            'supportWhatsApp' => preg_replace('/\D+/', '', (string) ($_ENV['SUPPORT_WHATSAPP'] ?? '5571997087082')) ?: '5571997087082',
        ]);
    }

    public function status(): Response
    {
        $checks = [
            'app' => true,
            'database' => false,
            'storage' => is_writable($this->app->root() . '/storage'),
        ];
        try {
            \App\Core\Database::connection()->query('SELECT 1');
            $checks['database'] = true;
        } catch (\Throwable) {
            $checks['database'] = false;
        }
        $ok = $checks['app'] && $checks['database'] && $checks['storage'];

        return $this->view('home/status', [
            'title' => 'Status do sistema',
            'checks' => $checks,
            'ok' => $ok,
        ], $ok ? 200 : 503);
    }
}