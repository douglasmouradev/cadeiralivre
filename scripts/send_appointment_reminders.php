<?php

declare(strict_types=1);

/**
 * Envia lembretes de agendamento (e-mail + WhatsApp link) ~24h antes.
 * Cron: php scripts/send_appointment_reminders.php
 */

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';
require $root . '/config/load_env.php';

if (!app_load_dotenv($root)) {
    fwrite(STDERR, "Sem .env\n");
    exit(1);
}

use App\Models\AppointmentModel;
use App\Services\Integrations\WhatsAppNotificationService;
use App\Services\MailService;

$cfg = require $root . '/config/mail.php';
$mail = new MailService($cfg);
$wa = new WhatsAppNotificationService();
$ap = new AppointmentModel();
$rows = $ap->dueForReminder(24);
$sent = 0;

foreach ($rows as $row) {
    $tid = (int) $row['tenant_id'];
    $id = (int) $row['id'];
    $email = trim((string) ($row['client_email'] ?? ''));
    $name = (string) ($row['client_name'] ?? 'Cliente');
    $tenantName = (string) ($row['tenant_name'] ?? '');
    $start = (string) ($row['start_datetime'] ?? '');
    $service = (string) ($row['service_name'] ?? '');
    $barber = (string) ($row['barber_name'] ?? '');
    $base = rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');
    $slug = (string) ($row['tenant_slug'] ?? '');
    $portalUrl = $slug !== '' ? $base . '/agendar/' . rawurlencode($slug) . '/meus-agendamentos' : $base;

    $body = '<p>Olá ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p>'
        . '<p>Lembrete do seu agendamento em <strong>' . htmlspecialchars($tenantName, ENT_QUOTES, 'UTF-8') . '</strong>:</p>'
        . '<ul><li>Data/hora: ' . htmlspecialchars($start, ENT_QUOTES, 'UTF-8') . '</li>'
        . '<li>Serviço: ' . htmlspecialchars($service, ENT_QUOTES, 'UTF-8') . '</li>'
        . '<li>Profissional: ' . htmlspecialchars($barber, ENT_QUOTES, 'UTF-8') . '</li></ul>'
        . '<p><a href="' . htmlspecialchars($portalUrl, ENT_QUOTES, 'UTF-8') . '">Ver ou reagendar</a></p>';

    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        try {
            $mail->send($email, $name, 'Lembrete de agendamento — ' . $tenantName, $body);
        } catch (Throwable $e) {
            fwrite(STDERR, "E-mail falhou #{$id}: {$e->getMessage()}\n");
            continue;
        }
    }

    $phone = (string) ($row['client_phone'] ?? '');
    if ($phone !== '') {
        $msg = "Olá {$name}! Lembrete: amanhã você tem {$service} com {$barber} às {$start} em {$tenantName}. {$portalUrl}";
        $wa->sendReminder($phone, $msg);
    }

    $ap->markReminderSent($tid, $id);
    $sent++;
    fwrite(STDOUT, "Lembrete #{$id} → {$name}\n");
}

fwrite(STDOUT, "Total enviados: {$sent}\n");
