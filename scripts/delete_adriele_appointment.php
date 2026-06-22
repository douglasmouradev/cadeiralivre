<?php

declare(strict_types=1);

/**
 * Remove agendamento cancelado da loja Adriele (Douglas, 2026-06-22 09:15).
 * Uso: php scripts/delete_adriele_appointment.php
 */

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';
require $root . '/config/load_env.php';

if (!app_load_dotenv($root)) {
    fwrite(STDERR, "Nenhum .env encontrado.\n");
    exit(1);
}

use App\Models\AppointmentModel;
use App\Models\TenantModel;

const SLUG = 'adriele-cardoso-nail-design';

$tenant = (new TenantModel())->findBySlug(SLUG);
if ($tenant === null) {
    fwrite(STDERR, "Loja não encontrada.\n");
    exit(1);
}

$tid = (int) $tenant['id'];
$pdo = \App\Core\Database::connection();
$stmt = $pdo->prepare(
    "SELECT a.id, a.start_datetime, c.name AS client_name
     FROM appointments a
     INNER JOIN clients c ON c.id = a.client_id
     WHERE a.tenant_id = :t AND c.name LIKE :name AND a.status = 'cancelled'
     ORDER BY a.start_datetime DESC"
);
$stmt->execute(['t' => $tid, 'name' => '%Douglas%']);
$rows = $stmt->fetchAll() ?: [];

if ($rows === []) {
    fwrite(STDOUT, "Nenhum agendamento cancelado encontrado para Douglas.\n");
    exit(0);
}

$ap = new AppointmentModel();
foreach ($rows as $row) {
    $id = (int) $row['id'];
    try {
        $ap->delete($tid, $id);
        fwrite(STDOUT, "Excluído #{$id} — {$row['client_name']} em {$row['start_datetime']}\n");
    } catch (Throwable $e) {
        fwrite(STDERR, "Erro #{$id}: {$e->getMessage()}\n");
    }
}
