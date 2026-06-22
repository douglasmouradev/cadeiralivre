<?php

declare(strict_types=1);

use App\Helpers\Flash;

/** @var array<string, mixed> $tenant */
/** @var string $slug */
/** @var string $csrf */

$brandHex = tenant_brand_hex((string) ($tenant['primary_color'] ?? ''));
$flashSuccess = Flash::get('success');
$flashError = Flash::get('error');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e($csrf) ?>">
    <meta name="theme-color" content="<?= e($brandHex) ?>">
    <title><?= e($title) ?></title>
    <?php require __DIR__ . '/../partials/public_tenant_head.php'; ?>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="public-body public-theme" style="--tenant-accent: <?= e($brandHex) ?>;">
<main class="public-centered">
    <div class="auth-card">
        <h1 class="mt-0">Entrar como cliente</h1>
        <p class="muted"><?= e((string) $tenant['name']) ?> — é necessário ter conta para agendar. A conta fica ativa na hora, sem confirmação por e-mail.</p>
        <?php if (is_string($flashSuccess) && $flashSuccess !== ''): ?>
            <div class="alert alert-success"><?= e($flashSuccess) ?></div>
        <?php endif; ?>
        <?php if (is_string($flashError) && $flashError !== ''): ?>
            <div class="alert alert-error"><?= e($flashError) ?></div>
        <?php endif; ?>
        <form method="post" action="/cliente/<?= e($slug) ?>/entrar" data-validate="1">
            <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
            <div class="row">
                <label for="pe">E-mail</label>
                <input id="pe" name="email" type="email" required autocomplete="email">
            </div>
            <div class="row">
                <label for="pp">Senha</label>
                <input id="pp" name="password" type="password" required autocomplete="current-password" minlength="8">
            </div>
            <button class="btn" type="submit">Entrar e agendar</button>
        </form>
        <p class="muted mt-1"><a href="/cliente/<?= e($slug) ?>/cadastro">Criar conta</a></p>
    </div>
</main>
<script src="/assets/js/app.js" defer></script>
</body>
</html>
