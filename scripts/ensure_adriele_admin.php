<?php

declare(strict_types=1);

/**
 * Garante perfil administrativo (dona) da Adriele — acesso ao painel, clientes, serviços etc.
 * Uso: php scripts/ensure_adriele_admin.php
 *      php scripts/ensure_adriele_admin.php "NovaSenha123"
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

const SLUG = 'adriele-cardoso-nail-design';
const ADMIN_EMAIL = 'adriele@adrielecardoso.com.br';
const ADMIN_NAME = 'Adriele Cardoso';
const DEFAULT_PASSWORD = 'Senha1234';

$password = (string) ($argv[1] ?? DEFAULT_PASSWORD);
if (strlen($password) < 8) {
    fwrite(STDERR, "A senha deve ter no mínimo 8 caracteres.\n");
    exit(1);
}

$tenants = new TenantModel();
$tenant = $tenants->findBySlug(SLUG);
if ($tenant === null) {
    fwrite(STDERR, "Loja não encontrada. Rode antes: php scripts/create_adriele_store.php\n");
    exit(1);
}

$tenantId = (int) $tenant['id'];
$users = new UserModel();
$user = $users->findByEmail(ADMIN_EMAIL);
$hash = password_hash($password, PASSWORD_BCRYPT);
$pdo = Database::connection();

if ($user === null) {
    $users->create($tenantId, ADMIN_NAME, ADMIN_EMAIL, $hash, UserRole::Owner->value, null);
    fwrite(STDOUT, "Perfil administrativo criado.\n");
} else {
    $uid = (int) $user['id'];
    $stmt = $pdo->prepare(
        'UPDATE users SET tenant_id = :tid, role = :role, name = :name, password_hash = :ph, is_active = 1, updated_at = NOW() WHERE id = :id'
    );
    $stmt->execute([
        'tid' => $tenantId,
        'role' => UserRole::Owner->value,
        'name' => ADMIN_NAME,
        'ph' => $hash,
        'id' => $uid,
    ]);
    fwrite(STDOUT, "Perfil administrativo atualizado (papel: dona).\n");
}

$base = rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');

fwrite(STDOUT, "\n");
fwrite(STDOUT, "Loja: " . (string) $tenant['name'] . "\n");
fwrite(STDOUT, "Login: {$base}/login\n");
fwrite(STDOUT, "E-mail: " . ADMIN_EMAIL . "\n");
fwrite(STDOUT, "Senha: {$password}\n");
fwrite(STDOUT, "\nApós entrar, acesse o painel em {$base}/painel\n");
fwrite(STDOUT, "Lá você administra: clientes, serviços, profissionais, agenda e configurações.\n");
fwrite(STDOUT, "\nNota: o e-mail profissional@adrielecardoso.com.br abre só a agenda; use o e-mail acima para administrar.\n");
