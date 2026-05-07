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
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="public-body public-theme" style="--tenant-accent: <?= e($brandHex) ?>;">
<main class="public-centered">
    <div class="auth-card">
        <h1 class="mt-0">Criar conta de cliente</h1>
        <p class="muted"><?= e((string) $tenant['name']) ?> — use o mesmo e-mail se já for cliente cadastrado na barbearia (ativamos o acesso online).</p>
        <?php if (is_string($flashSuccess) && $flashSuccess !== ''): ?>
            <div class="alert alert-success"><?= e($flashSuccess) ?></div>
        <?php endif; ?>
        <?php if (is_string($flashError) && $flashError !== ''): ?>
            <div class="alert alert-error"><?= e($flashError) ?></div>
        <?php endif; ?>
        <form method="post" action="/cliente/<?= e($slug) ?>/cadastro" data-validate="1">
            <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
            <div class="row">
                <label for="pn">Nome completo</label>
                <input id="pn" name="name" required autocomplete="name">
            </div>
            <div class="row">
                <label for="pe">E-mail</label>
                <input id="pe" name="email" type="email" required autocomplete="email">
            </div>
            <div class="row">
                <label for="pt">Telefone</label>
                <input id="pt" name="phone" type="tel" autocomplete="tel">
            </div>
            <div class="row">
                <label for="pp">Senha (mín. 8 caracteres)</label>
                <input id="pp" name="password" type="password" minlength="8" required autocomplete="new-password">
            </div>
            <button class="btn" type="submit">Criar conta e agendar</button>
        </form>
        <p class="muted mt-1"><a href="/cliente/<?= e($slug) ?>/entrar">Já tenho conta</a> · <a href="/agendar/<?= e($slug) ?>">Agendar sem conta</a></p>
    </div>
</main>
<script src="/assets/js/app.js" defer></script>
</body>
</html>
