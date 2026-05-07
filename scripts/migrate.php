<?php

declare(strict_types=1);

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';

require $root . '/config/load_env.php';
if (!app_load_dotenv($root)) {
    fwrite(STDERR, "Nenhum .env encontrado. Coloque .env em cadeira-livre/ ou na pasta pai, ou copie de .env.example.\n");
    exit(1);
}

$config = require $root . '/config/database.php';

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $config['host'],
    $config['port'],
    $config['database'],
    $config['charset']
);

try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "Falha ao conectar ao MySQL: " . $e->getMessage() . PHP_EOL);
    fwrite(STDERR, "Verifique em .env: DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD" . PHP_EOL);
    exit(1);
}

$tableExists = static function (PDO $pdo, string $name): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?'
    );
    $stmt->execute([$name]);

    return (int) $stmt->fetchColumn() > 0;
};

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        migration VARCHAR(191) NOT NULL PRIMARY KEY,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$dir = $root . '/database/migrations';
$files = glob($dir . '/*.sql') ?: [];
sort($files, SORT_STRING);

$applied = (int) $pdo->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn();
if ($applied === 0 && $tableExists($pdo, 'tenants')) {
    $ins = $pdo->prepare('INSERT IGNORE INTO schema_migrations (migration) VALUES (?)');
    foreach ($files as $file) {
        $ins->execute([basename($file)]);
    }
    fwrite(STDOUT, "Esquema já existia sem registro de migrations; arquivos marcados como aplicados.\n");

    exit(0);
}

$check = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE migration = ? LIMIT 1');
$mark = $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (?)');

foreach ($files as $file) {
    $name = basename($file);
    $check->execute([$name]);
    if ($check->fetchColumn()) {
        fwrite(STDOUT, 'SKIP: ' . $name . PHP_EOL);

        continue;
    }
    $sql = file_get_contents($file);
    if (!is_string($sql) || trim($sql) === '') {
        continue;
    }
    $pdo->exec($sql);
    $mark->execute([$name]);
    fwrite(STDOUT, 'OK: ' . $name . PHP_EOL);
}

fwrite(STDOUT, 'Migrations concluídas.' . PHP_EOL);
