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
            'demoBookingUrl' => demo_booking_url(),
            'demoBookingLabel' => demo_booking_label(),
            'baseUrl' => app_base_url(),
            'supportWhatsApp' => preg_replace('/\D+/', '', (string) ($_ENV['SUPPORT_WHATSAPP'] ?? '5571997087082')) ?: '5571997087082',
        ]);
    }

    public function robots(): Response
    {
        $base = app_base_url();
        $body = "User-agent: *\nAllow: /\nDisallow: /painel\nDisallow: /agenda\nDisallow: /saas\nDisallow: /login\nDisallow: /cadastro\n";
        if ($base !== '') {
            $body .= "\nSitemap: {$base}/sitemap.xml\n";
        }

        return Response::text($body, 200);
    }

    public function sitemap(): Response
    {
        $base = app_base_url();
        $urls = [$base !== '' ? $base . '/' : '/'];
        foreach ((new \App\Models\TenantModel())->listPublicDirectory() as $tenant) {
            $slug = (string) ($tenant['slug'] ?? '');
            if ($slug !== '') {
                $urls[] = ($base !== '' ? $base : '') . '/agendar/' . rawurlencode($slug);
            }
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $url) {
            $xml .= '  <url><loc>' . htmlspecialchars($url, ENT_XML1) . '</loc></url>' . "\n";
        }
        $xml .= '</urlset>';

        return Response::text($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function status(): Response
    {
        $checks = [
            'app' => true,
            'database' => false,
            'storage' => is_writable($this->app->root() . '/storage'),
            'cron_mail' => \App\Helpers\CronHeartbeat::isRecent('mail_queue', 30),
            'cron_reminders' => \App\Helpers\CronHeartbeat::isRecent('appointment_reminders', 180),
            'cron_whatsapp' => \App\Helpers\CronHeartbeat::isRecent('whatsapp_queue', 30),
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