<?php

declare(strict_types=1);

namespace App\Services\Integrations;

/**
 * Ponto de extensão para sincronizar agendamentos com Google Calendar (OAuth2 + API).
 * Implemente push/pull conforme a política da barbearia; não ativo por padrão.
 */
final class GoogleCalendarSyncService
{
    /** @param array<string, mixed> $appointment */
    public function pushAppointment(int $tenantId, array $appointment): void
    {
        // Intencionalmente vazio: ligar a Google Calendar API aqui.
    }
}
