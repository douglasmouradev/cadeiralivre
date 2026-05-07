<?php

declare(strict_types=1);

/** @var string $token */
/** @var string $csrf */

$brandHex = tenant_brand_hex(null);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e($csrf) ?>">
    <meta name="theme-color" content="<?= e($brandHex) ?>">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="public-body public-theme" style="--tenant-accent: <?= e($brandHex) ?>;">
<main class="public-centered">
    <div class="auth-card">
        <h1>Cancelar</h1>
        <p class="muted">Esta ação não pode ser desfeita.</p>
        <form method="post" action="/agendar/cancelar" onsubmit="return App.confirm('Cancelar este agendamento?');">
            <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <div class="form-actions mt-1">
                <button class="btn danger" type="submit">Confirmar cancelamento</button>
            </div>
        </form>
    </div>
</main>
<script src="/assets/js/app.js" defer></script>
</body>
</html>
