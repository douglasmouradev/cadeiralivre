<?php

declare(strict_types=1);

namespace App\Services\Integrations;

/**
 * Ponto de extensão para lembretes por WhatsApp (ex.: API oficial Meta, Twilio, Z-API).
 * Não envia mensagens até configurar credenciais e templates aprovados.
 */
final class WhatsAppNotificationService
{
    public function sendReminder(string $phoneE164, string $message): void
    {
        // Intencionalmente vazio.
    }
}
