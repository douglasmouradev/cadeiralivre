<?php

declare(strict_types=1);

/**
 * Cria ou atualiza superadmin da plataforma (tenant_id NULL).
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
$existing = $users->findByEmail($email);
$hash = password_hash($password, PASSWORD_BCRYPT);
$pdo = Database::connection();

if ($existing === null) {
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
    fwrite(STDOUT, "Superadmin criado.\n");
} else {
    $stmt = $pdo->prepare(
        'UPDATE users SET tenant_id = NULL, name = :n, password_hash = :p, role = :r, is_active = 1, updated_at = NOW() WHERE id = :id'
    );
    $stmt->execute([
        'n' => $name,
        'p' => $hash,
        'r' => UserRole::Superadmin->value,
        'id' => (int) $existing['id'],
    ]);
    fwrite(STDOUT, "Utilizador promovido a superadmin da plataforma.\n");
}

$base = rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');

fwrite(STDOUT, "\n");
fwrite(STDOUT, "Login: {$base}/login\n");
fwrite(STDOUT, "E-mail: {$email}\n");
fwrite(STDOUT, "Painel SaaS: {$base}/saas\n");
fwrite(STDOUT, "\nApós entrar você verá todas as lojas da plataforma com a logo do " . (trim((string) ($_ENV['APP_NAME'] ?? '')) ?: 'CadeiraLivre') . ".\n");
