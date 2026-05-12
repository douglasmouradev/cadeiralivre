<?php

declare(strict_types=1);

/**
 * Cria utilizador superadmin (tenant_id NULL) para gestão /saas/tenants.
 * Uso: php scripts/create_superadmin.php email@dominio.com "SenhaSegura8" "Nome"
 */

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';
require $root . '/config/load_env.php';
if (!app_load_dotenv($root)) {
    fwrite(STDERR, "Sem .env\n");
    exit(1);
}

use App\Core\Database;
use App\Enums\UserRole;
use App\Models\UserModel;

$email = mb_strtolower(trim((string) ($argv[1] ?? '')));
$password = (string) ($argv[2] ?? '');
$name = trim((string) ($argv[3] ?? 'Super administrador'));

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Uso: php scripts/create_superadmin.php email senha [nome]\n");
    exit(1);
}
if (strlen($password) < 8) {
    fwrite(STDERR, "Senha com mínimo 8 caracteres.\n");
    exit(1);
}

$users = new UserModel();
if ($users->findByEmail($email) !== null) {
    fwrite(STDERR, "E-mail já registado.\n");
    exit(1);
}

$pdo = Database::connection();
$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $pdo->prepare(
    'INSERT INTO users (tenant_id, name, email, password_hash, role, phone, is_active, created_at, updated_at)
     VALUES (NULL, :n, :e, :p, :r, NULL, 1, NOW(), NOW())'
);
$stmt->execute([
    'n' => $name,
    'e' => $email,
    'p' => $hash,
    'r' => UserRole::Superadmin->value,
]);

fwrite(STDOUT, "Superadmin criado. Entre em /login com {$email}\n");
