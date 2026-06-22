<?php

declare(strict_types=1);

/**
 * Remove cliente da loja Adriele por e-mail.
 * Uso: php scripts/delete_adriele_client.php
 *      php scripts/delete_adriele_client.php outro@email.com
 */

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';
require $root . '/config/load_env.php';

if (!app_load_dotenv($root)) {
    fwrite(STDERR, "Nenhum .env encontrado.\n");
    exit(1);
}

use App\Models\ClientModel;
use App\Models\TenantModel;

const SLUG = 'adriele-cardoso-nail-design';
const DEFAULT_EMAIL = 'douglas@titaniumtelecom.com.br';

$email = mb_strtolower(trim((string) ($argv[1] ?? DEFAULT_EMAIL)));
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Uso: php scripts/delete_adriele_client.php [email]\n");
    exit(1);
}

$tenant = (new TenantModel())->findBySlug(SLUG);
if ($tenant === null) {
    fwrite(STDERR, "Loja não encontrada: " . SLUG . "\n");
    exit(1);
}

$tid = (int) $tenant['id'];
$clients = new ClientModel();
$client = $clients->findByTenantEmail($tid, $email);
if ($client === null) {
    fwrite(STDOUT, "Nenhum cliente com e-mail {$email} na loja Adriele.\n");
    exit(0);
}

$id = (int) $client['id'];
$name = (string) ($client['name'] ?? '');
$appts = $clients->countAppointments($tid, $id);

try {
    $clients->deleteWithAppointments($tid, $id);
} catch (Throwable $e) {
    fwrite(STDERR, 'Erro ao excluir: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, "Cliente excluído: {$name} ({$email})\n");
if ($appts > 0) {
    fwrite(STDOUT, "Agendamentos removidos: {$appts}\n");
}
