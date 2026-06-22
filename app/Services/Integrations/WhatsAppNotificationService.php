<?php

declare(strict_types=1);

namespace App\Services\Integrations;

/**
 * Lembretes WhatsApp: API opcional (WHATSAPP_API_URL) ou apenas gera link wa.me.
 */
final class WhatsAppNotificationService
{
    public function sendReminder(string $phoneE164, string $message): void
    {
        $phone = preg_replace('/\D+/', '', $phoneE164) ?? '';
        if ($phone === '') {
            return;
        }
        if (str_starts_with($phone, '0')) {
            $phone = ltrim($phone, '0');
        }
        if (!str_starts_with($phone, '55') && strlen($phone) >= 10 && strlen($phone) <= 11) {
            $phone = '55' . $phone;
        }

        $apiUrl = trim((string) ($_ENV['WHATSAPP_API_URL'] ?? ''));
        $apiToken = trim((string) ($_ENV['WHATSAPP_API_TOKEN'] ?? ''));
        if ($apiUrl !== '' && $apiToken !== '') {
            $payload = json_encode(['phone' => $phone, 'message' => $message], JSON_UNESCAPED_UNICODE);
            if ($payload !== false) {
                $ctx = stream_context_create([
                    'http' => [
                        'method' => 'POST',
                        'header' => "Content-Type: application/json\r\nAuthorization: Bearer {$apiToken}\r\n",
                        'content' => $payload,
                        'timeout' => 8,
                        'ignore_errors' => true,
                    ],
                ]);
                @file_get_contents($apiUrl, false, $ctx);
            }

            return;
        }
        // Sem API: link wa.me fica disponível via whatsapp_link() nas views.
    }
}
