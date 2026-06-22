<?php

declare(strict_types=1);

/** @var string $title */
/** @var string $csrf */

$flashSuccess = \App\Helpers\Flash::get('success');
$flashError = \App\Helpers\Flash::get('error');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="<?= e($csrf) ?>">
    <title><?= e($title) ?> — <?= e(app_name()) ?></title>
    <?php require __DIR__ . '/../partials/site_favicons.php'; ?>
    <link rel="manifest" href="/manifest.json">
    <link rel="stylesheet" href="<?= e(asset_version('/assets/css/app.css')) ?>">
</head>
<body>
<div class="auth-page">
    <div class="auth-card auth-card--login">
        <div class="auth-brand">
            <img src="/assets/img/cadeiralivre-logo.png" width="160" height="160" alt="<?= e(app_name()) ?>">
        </div>
        <h1>Entrar</h1>
        <?php if (is_string($flashSuccess) && $flashSuccess !== ''): ?>
            <p class="alert alert-success" role="status"><?= e($flashSuccess) ?></p>
        <?php endif; ?>
        <?php if (is_string($flashError) && $flashError !== ''): ?>
            <p class="alert alert-error" role="alert"><?= e($flashError) ?></p>
        <?php endif; ?>
        <form method="post" action="/login" data-validate="1">
            <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
            <div class="row">
                <label for="email">E-mail</label>
                <input id="email" name="email" type="text" inputmode="email" autocomplete="username" required spellcheck="false">
            </div>
            <div class="row">
                <label for="password">Senha</label>
                <input id="password" name="password" type="password" required autocomplete="current-password" minlength="8">
            </div>
            <div class="row row--checkbox">
                <label><input type="checkbox" name="remember" value="1"> Lembrar por 30 dias</label>
            </div>
            <button class="btn" type="submit" data-loading="1">Entrar</button>
        </form>
        <p class="muted mt-1 auth-card__divider">ou</p>
        <p class="mt-1 auth-card__cta-wrap"><a class="btn secondary auth-card__cta-secondary" href="/primeiro-acesso">Primeiro acesso (sou cliente)</a></p>
        <p class="muted mt-1 auth-card__links"><a href="/esqueci-senha">Esqueci a senha</a></p>
        <?php require __DIR__ . '/../partials/auth_site_footer.php'; ?>
    </div>
</div>
<script src="/assets/js/app.js" defer></script>
</body>
</html>
