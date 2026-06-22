<?php

declare(strict_types=1);

use App\Helpers\Flash;

/** @var string $title */
/** @var string $csrf */

$flashSuccess = Flash::get('success');
$flashError = Flash::get('error');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="<?= e($csrf) ?>">
    <title><?= e($title) ?> — <?= e(app_name()) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-card auth-card--login">
        <div class="auth-brand">
            <img src="/assets/img/cadeiralivre-logo.png" width="160" height="160" alt="<?= e(app_name()) ?>">
        </div>
        <h1>Primeiro acesso</h1>
        <p class="muted auth-card__intro">Crie sua conta de <strong>cliente</strong>. Na próxima etapa você escolhe a barbearia e já pode agendar.</p>
        <?php if (is_string($flashSuccess) && $flashSuccess !== ''): ?>
            <p class="alert alert-success" role="status"><?= e($flashSuccess) ?></p>
        <?php endif; ?>
        <?php if (is_string($flashError) && $flashError !== ''): ?>
            <p class="alert alert-error" role="alert"><?= e($flashError) ?></p>
        <?php endif; ?>
        <form method="post" action="/primeiro-acesso" data-validate="1">
            <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
            <div class="row">
                <label for="on-name">Nome completo</label>
                <input id="on-name" name="name" required autocomplete="name">
            </div>
            <div class="row">
                <label for="on-email">E-mail</label>
                <input id="on-email" name="email" type="text" inputmode="email" required autocomplete="email" spellcheck="false">
            </div>
            <div class="row">
                <label for="on-phone">Telefone (opcional)</label>
                <input id="on-phone" name="phone" type="tel" autocomplete="tel">
            </div>
            <div class="row">
                <label for="on-pass">Senha (mín. 8 caracteres)</label>
                <input id="on-pass" name="password" type="password" minlength="8" required autocomplete="new-password">
            </div>
            <button class="btn" type="submit" data-loading="1">Continuar</button>
        </form>
        <p class="muted mt-1 auth-card__links"><a href="/login">Entrar (equipe)</a></p>
    </div>
    <?php require __DIR__ . '/../partials/auth_site_footer.php'; ?>
</div>
<script src="/assets/js/app.js" defer></script>
</body>
</html>
