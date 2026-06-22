<?php

declare(strict_types=1);

/**
 * Cria ou atualiza utilizador dono (owner) de uma loja.
 * Uso: php scripts/ensure_owner_user.php email@dominio.com "SenhaSegura8" "Nome Completo" [slug-da-loja]
 *
 * slug padrão: demo-barbearia
 */

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';
require $root . '/config/load_env.php';

if (!app_load_dotenv($root)) {
    fwrite(STDERR, "Nenhum .env encontrado.\n");
    exit(1);
}

use App\Core\Database;
use App\Enums\UserRole;
use App\Models\TenantModel;
use App\Models\UserModel;

$email = mb_strtolower(trim((string) ($argv[1] ?? '')));
$password = (string) ($argv[2] ?? '');
$name = trim((string) ($argv[3] ?? ''));
$slug = trim((string) ($argv[4] ?? 'demo-barbearia'));

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Uso: php scripts/ensure_owner_user.php email senha \"Nome\" [slug-loja]\n");
    exit(1);
}
if (strlen($password) < 8) {
    fwrite(STDERR, "A senha deve ter no mínimo 8 caracteres.\n");
    exit(1);
}
if ($name === '') {
    fwrite(STDERR, "Informe o nome do dono.\n");
    exit(1);
}

$tenants = new TenantModel();
$tenant = $tenants->findBySlug($slug);
if ($tenant === null) {
    fwrite(STDERR, "Loja não encontrada (slug: {$slug}).\n");
    exit(1);
}

$tenantId = (int) $tenant['id'];
$users = new UserModel();
$user = $users->findByEmail($email);
$hash = password_hash($password, PASSWORD_BCRYPT);
$pdo = Database::connection();

if ($user === null) {
    $users->create($tenantId, $name, $email, $hash, UserRole::Owner->value, null);
    fwrite(STDOUT, "Perfil de dono criado.\n");
} else {
    $uid = (int) $user['id'];
    $stmt = $pdo->prepare(
        'UPDATE users SET tenant_id = :tid, role = :role, name = :name, password_hash = :ph, is_active = 1, updated_at = NOW() WHERE id = :id'
    );
    $stmt->execute([
        'tid' => $tenantId,
        'role' => UserRole::Owner->value,
        'name' => $name,
        'ph' => $hash,
        'id' => $uid,
    ]);
    fwrite(STDOUT, "Perfil de dono atualizado.\n");
}

$base = rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');

fwrite(STDOUT, "\n");
fwrite(STDOUT, 'Loja: ' . (string) $tenant['name'] . " ({$slug})\n");
fwrite(STDOUT, "Login: {$base}/login\n");
fwrite(STDOUT, "E-mail: {$email}\n");
fwrite(STDOUT, "Painel: {$base}/painel\n");
