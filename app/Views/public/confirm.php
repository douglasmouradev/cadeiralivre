<?php

declare(strict_types=1);

/** @var array<string, mixed> $tenant */
/** @var string $slug */
/** @var string $token */
/** @var string $csrf */

$brandHex = tenant_brand_hex((string) ($tenant['primary_color'] ?? ''));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="<?= e($csrf) ?>">
    <meta name="theme-color" content="<?= e($brandHex) ?>">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="public-body public-theme" style="--tenant-accent: <?= e($brandHex) ?>;">
<main class="public-centered">
    <div class="auth-card">
        <h1>Confirmar agendamento</h1>
        <p class="muted">Digite o código de 6 dígitos enviado por e-mail.</p>
        <form method="post" action="/agendar/<?= e($slug) ?>/confirmar" data-validate="1">
            <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <div class="row">
                <label for="code">Código</label>
                <input id="code" name="code" required pattern="\d{6}" maxlength="6" inputmode="numeric" autocomplete="one-time-code">
            </div>
            <div class="form-actions mt-1">
                <button class="btn" type="submit">Confirmar</button>
            </div>
        </form>
    </div>
</main>
<div id="toast-root" class="toast-root" aria-live="polite"></div>
<script src="/assets/js/app.js" defer></script>
</body>
</html>
