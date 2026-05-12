<?php

declare(strict_types=1);

/**
 * Processa fila outbound_emails (cron, ex.: a cada 5 minutos).
 * Uso: php scripts/process_mail_queue.php
 */

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';
require $root . '/config/load_env.php';
if (!app_load_dotenv($root)) {
    fwrite(STDERR, "Sem .env\n");
    exit(1);
}

$config = require $root . '/config/mail.php';
if (empty($config['queue'])) {
    fwrite(STDOUT, "MAIL_QUEUE não está ativo; nada a processar.\n");
    exit(0);
}

use App\Models\OutboundEmailModel;
use App\Services\MailService;

$sendCfg = array_merge($config, ['queue' => false]);
$mail = new MailService($sendCfg);

$q = new OutboundEmailModel();
$rows = $q->fetchPending(30);
if ($rows === []) {
    fwrite(STDOUT, "Fila vazia.\n");
    exit(0);
}

foreach ($rows as $row) {
    $id = (int) $row['id'];
    try {
        $mail->send(
            (string) $row['to_email'],
            (string) $row['to_name'],
            (string) $row['subject'],
            (string) $row['body_html'],
        );
        $q->markSent($id);
        fwrite(STDOUT, "OK #{$id}\n");
    } catch (Throwable $e) {
        $q->markRetry($id, $e->getMessage());
        fwrite(STDERR, "Falha #{$id}: " . $e->getMessage() . "\n");
    }
}
