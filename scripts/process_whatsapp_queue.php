<?php

declare(strict_types=1);

/**
 * Processa fila outbound_whatsapp (cron, ex.: a cada 5 minutos).
 */

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';
require $root . '/config/load_env.php';

if (!app_load_dotenv($root)) {
    fwrite(STDERR, "Sem .env\n");
    exit(1);
}

use App\Helpers\CronHeartbeat;
use App\Models\OutboundWhatsAppModel;
use App\Services\Integrations\WhatsAppNotificationService;

$wa = new WhatsAppNotificationService();
if (!$wa->apiConfigured()) {
    fwrite(STDOUT, "WhatsApp API não configurada — nada a processar.\n");
    exit(0);
}

$model = new OutboundWhatsAppModel();
$rows = $model->fetchPending(30);
$sent = 0;

foreach ($rows as $row) {
    $id = (int) ($row['id'] ?? 0);
    $phone = (string) ($row['phone'] ?? '');
    $message = (string) ($row['message'] ?? '');
    if ($id <= 0 || $phone === '' || $message === '') {
        continue;
    }
    if ($wa->sendNow($phone, $message)) {
        $model->markSent($id);
        $sent++;
    } else {
        $model->markRetry($id, 'Falha no envio HTTP');
    }
}

CronHeartbeat::touch('whatsapp_queue');
fwrite(STDOUT, "WhatsApp enviados: {$sent}\n");
