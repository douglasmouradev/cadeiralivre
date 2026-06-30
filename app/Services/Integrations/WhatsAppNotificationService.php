<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Models\OutboundWhatsAppModel;

/**
 * Lembretes WhatsApp via API (Z-API, Evolution, etc.) com fila opcional.
 */
final class WhatsAppNotificationService
{
    public function sendReminder(string $phoneE164, string $message): void
    {
        $phone = $this->normalizePhone($phoneE164);
        if ($phone === '') {
            return;
        }

        if ($this->shouldQueue()) {
            (new OutboundWhatsAppModel())->enqueue($phone, $message);

            return;
        }

        $this->sendNow($phone, $message);
    }

    public function sendNow(string $phone, string $message): bool
    {
        $apiUrl = trim((string) ($_ENV['WHATSAPP_API_URL'] ?? ''));
        $apiToken = trim((string) ($_ENV['WHATSAPP_API_TOKEN'] ?? ''));
        if ($apiUrl === '' || $apiToken === '') {
            return false;
        }

        $payload = json_encode(['phone' => $phone, 'message' => $message], JSON_UNESCAPED_UNICODE);
        if ($payload === false) {
            return false;
        }

        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAuthorization: Bearer {$apiToken}\r\n",
                'content' => $payload,
                'timeout' => 12,
                'ignore_errors' => true,
            ],
        ]);
        $response = @file_get_contents($apiUrl, false, $ctx);
        if ($response === false) {
            return false;
        }

        return true;
    }

    public function apiConfigured(): bool
    {
        return trim((string) ($_ENV['WHATSAPP_API_URL'] ?? '')) !== ''
            && trim((string) ($_ENV['WHATSAPP_API_TOKEN'] ?? '')) !== '';
    }

    private function shouldQueue(): bool
    {
        if (!$this->apiConfigured()) {
            return false;
        }
        $flag = $_ENV['WHATSAPP_QUEUE'] ?? 'true';

        return filter_var($flag, FILTER_VALIDATE_BOOL);
    }

    private function normalizePhone(string $phoneE164): string
    {
        $phone = preg_replace('/\D+/', '', $phoneE164) ?? '';
        if ($phone === '') {
            return '';
        }
        if (str_starts_with($phone, '0')) {
            $phone = ltrim($phone, '0');
        }
        if (!str_starts_with($phone, '55') && strlen($phone) >= 10 && strlen($phone) <= 11) {
            $phone = '55' . $phone;
        }

        return $phone;
    }
}
