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
    <title><?= e($title) ?> — <?= e(app_name()) ?></title>
    <?php require __DIR__ . '/../partials/site_favicons.php'; ?>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#f7f4ef">
    <link rel="stylesheet" href="<?= e(asset_version('/assets/css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_version('/assets/css/auth.css')) ?>">
</head>
<body class="auth-body">
<div class="auth-shell">
    <?php require __DIR__ . '/../partials/auth_brand_aside.php'; ?>
    <div class="auth-shell__main">
        <div class="auth-page status-page">
            <div class="auth-card">
                <h1 style="margin-top:0">Status do sistema</h1>
                <p class="pill <?= $ok ? 'pill--success' : 'pill--danger' ?>"><?= $ok ? 'Operacional' : 'Degradado' ?></p>
                <ul class="status-list">
                    <li><?= $checks['database'] ? '✓' : '✗' ?> Banco de dados</li>
                    <li><?= $checks['storage'] ? '✓' : '✗' ?> Armazenamento gravável</li>
                    <li>✓ Aplicação PHP</li>
                    <li><?= ($checks['cron_mail'] ?? false) ? '✓' : '○' ?> Cron fila de e-mail (30 min)</li>
                    <li><?= ($checks['cron_reminders'] ?? false) ? '✓' : '○' ?> Cron lembretes (3 h)</li>
                    <li><?= ($checks['cron_whatsapp'] ?? false) ? '✓' : '○' ?> Cron WhatsApp (30 min)</li>
                </ul>
                <p class="muted" style="font-size:0.82rem">○ = cron não detectado recentemente (configure em <code>scripts/cron.example.sh</code>).</p>
                <p class="muted" style="font-size:0.85rem">Atualizado em <?= e(date('d/m/Y H:i:s')) ?></p>
                <p><a href="/">← Voltar ao início</a></p>
            </div>
        </div>
        <?php require __DIR__ . '/../partials/auth_site_footer.php'; ?>
    </div>
</div>
</body>
</html>
