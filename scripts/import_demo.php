<?php

declare(strict_types=1);

/**
 * Importa database/seeds/001_demo.sql usando as credenciais do .env (evita erro com USUARIO literal no README).
 */

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';
require $root . '/config/load_env.php';

if (!app_load_dotenv($root)) {
    fwrite(STDERR, "Nenhum .env encontrado.\n");
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
    fwrite(STDERR, 'Não foi possível conectar ao MySQL: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$tenantsExists = (int) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'tenants'"
)->fetchColumn() > 0;

if ($tenantsExists) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM tenants WHERE slug = :slug');
    $stmt->execute(['slug' => 'demo-barbearia']);
    if ((int) $stmt->fetchColumn() > 0) {
        fwrite(STDOUT, "Seed de demonstração já está no banco (slug \"demo-barbearia\"). Nada a importar.\n");
        exit(0);
    }
}

$seed = $root . '/database/seeds/001_demo.sql';

if (!is_readable($seed)) {
    fwrite(STDERR, "Seed não encontrado: {$seed}\n");
    exit(1);
}

$sql = file_get_contents($seed);
if (!is_string($sql) || trim($sql) === '') {
    fwrite(STDERR, "Seed vazio.\n");
    exit(1);
}

$mysql = 'mysql';
if (PHP_OS_FAMILY === 'Windows') {
    $mysql = 'mysql.exe';
}

$env = [];
foreach (array_merge($_SERVER, $_ENV) as $k => $v) {
    if (!is_string($k) || !is_scalar($v)) {
        continue;
    }
    $env[$k] = (string) $v;
}
$env['MYSQL_PWD'] = (string) $config['password'];

$cmd = [
    $mysql,
    '-h',
    (string) $config['host'],
    '-P',
    (string) $config['port'],
    '-u',
    (string) $config['username'],
    (string) $config['database'],
];

$proc = proc_open($cmd, [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
], $pipes, null, $env);

if (!is_resource($proc)) {
    fwrite(STDERR, "Não foi possível iniciar o cliente mysql. Instale o MySQL client ou importe manualmente.\n");
    exit(1);
}

fwrite($pipes[0], $sql);
fclose($pipes[0]);
$stdout = stream_get_contents($pipes[1]);
$stderr = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$code = proc_close($proc);

if ($stdout !== false && $stdout !== '') {
    fwrite(STDOUT, $stdout);
}
if ($stderr !== false && $stderr !== '') {
    fwrite(STDERR, $stderr);
}

if ($code !== 0) {
    fwrite(STDERR, "Importação terminou com código {$code}.\n");
    exit($code);
}

fwrite(STDOUT, "Seed importado com sucesso.\n");
