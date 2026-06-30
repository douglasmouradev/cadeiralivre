<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Helpers\EmailTemplate;
use App\Models\AppointmentModel;
use App\Models\TenantModel;

/** Notifica o cliente por e-mail quando o status do agendamento muda no painel. */
final class AppointmentClientNotifier
{
    public function __construct(
        private readonly MailService $mail,
        private readonly AppointmentModel $appointments = new AppointmentModel(),
        private readonly TenantModel $tenants = new TenantModel(),
    ) {
    }

    public function notifyStatusChange(int $tenantId, int $appointmentId, string $newStatus): void
    {
        if (!in_array($newStatus, [
            AppointmentStatus::Confirmed->value,
            AppointmentStatus::Cancelled->value,
        ], true)) {
            return;
        }

        $row = $this->appointments->findWithClientDetails($tenantId, $appointmentId);
        if ($row === null) {
            return;
        }

        $email = trim((string) ($row['client_email'] ?? ''));
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return;
        }

        $tenant = $this->tenants->findById($tenantId);
        if ($tenant === null) {
            return;
        }

        $tenantName = (string) $tenant['name'];
        $tz = (string) ($tenant['timezone'] ?? 'America/Sao_Paulo');
        $accent = isset($tenant['primary_color']) ? (string) $tenant['primary_color'] : null;
        $clientName = (string) ($row['client_name'] ?? 'Cliente');
        $dtLabel = format_datetime_in_tenant_tz((string) ($row['start_datetime'] ?? ''), $tz);
        $serviceName = (string) ($row['service_name'] ?? '');

        if ($newStatus === AppointmentStatus::Confirmed->value) {
            $subject = 'Agendamento confirmado — ' . $tenantName;
            $body = EmailTemplate::paragraph('Olá <strong>' . e($clientName) . '</strong>,')
                . EmailTemplate::paragraph('Seu agendamento na <strong>' . e($tenantName) . '</strong> foi <strong>confirmado</strong>.')
                . EmailTemplate::paragraph('<strong>Data/hora:</strong> ' . e($dtLabel) . '<br><strong>Serviço:</strong> ' . e($serviceName));
        } else {
            $reason = trim((string) ($row['cancellation_reason'] ?? ''));
            $subject = 'Agendamento cancelado — ' . $tenantName;
            $body = EmailTemplate::paragraph('Olá <strong>' . e($clientName) . '</strong>,')
                . EmailTemplate::paragraph('Seu agendamento na <strong>' . e($tenantName) . '</strong> em <strong>' . e($dtLabel) . '</strong> foi <strong>cancelado</strong>.');
            if ($reason !== '') {
                $body .= EmailTemplate::paragraph('<strong>Motivo:</strong> ' . e($reason));
            }
        }

        $html = EmailTemplate::layout($body, $tenantName, $accent);

        try {
            $this->mail->send($email, $clientName, $subject, $html);
        } catch (\Throwable $e) {
            error_log('AppointmentClientNotifier: ' . $e->getMessage());
        }
    }
}
