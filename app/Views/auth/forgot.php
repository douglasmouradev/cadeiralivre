<?php

declare(strict_types=1);

/** @var string $title */
/** @var string $csrf */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e($csrf) ?>">
    <title><?= e($title) ?> — <?= e(app_name()) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-brand">
            <img src="/assets/img/cadeiralivre-logo.png" width="160" height="160" alt="<?= e(app_name()) ?>">
        </div>
        <h1>Recuperar senha</h1>
        <form method="post" action="/esqueci-senha" data-validate="1">
            <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
            <div class="row">
                <label for="email">E-mail</label>
                <input id="email" name="email" type="email" required>
            </div>
            <button class="btn" type="submit">Enviar link</button>
        </form>
        <p class="muted mt-1"><a href="/login">Voltar ao login</a></p>
    </div>
</div>
<script src="/assets/js/app.js" defer></script>
</body>
</html>
