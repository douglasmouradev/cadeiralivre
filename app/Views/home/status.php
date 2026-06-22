<?php

declare(strict_types=1);

/** @var array<string, bool> $checks */
/** @var bool $ok */
/** @var string $title */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($title) ?></title>
    <?php require __DIR__ . '/../partials/site_favicons.php'; ?>
    <link rel="manifest" href="/manifest.json">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="public-body">
<main class="public-centered" style="max-width:480px">
    <div class="card">
        <h1 style="margin-top:0">Status do sistema</h1>
        <p class="pill <?= $ok ? 'pill--success' : 'pill--danger' ?>"><?= $ok ? 'Operacional' : 'Degradado' ?></p>
        <ul class="status-list">
            <li><?= $checks['database'] ? '✓' : '✗' ?> Banco de dados</li>
            <li><?= $checks['storage'] ? '✓' : '✗' ?> Armazenamento gravável</li>
            <li>✓ Aplicação PHP</li>
        </ul>
        <p class="muted" style="font-size:0.85rem">Atualizado em <?= e(date('d/m/Y H:i:s')) ?></p>
        <p><a href="/">← Voltar ao início</a></p>
    </div>
</main>
</body>
</html>
