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

        return $this->view('home/landing', [
            'title' => app_name() . ' — Agendamento online para seu negócio',
            'plans' => $plans,
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