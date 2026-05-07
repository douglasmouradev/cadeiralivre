<?php

declare(strict_types=1);

/** @var string $title */
/** @var string $csrf */
/** @var string $token */
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
    <div class="auth-card auth-card--login">
        <div class="auth-brand">
            <img src="/assets/img/cadeiralivre-logo.png" width="160" height="160" alt="<?= e(app_name()) ?>">
        </div>
        <h1>Nova senha</h1>
        <form method="post" action="/redefinir-senha" data-validate="1">
            <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <div class="row">
                <label for="password">Nova senha</label>
                <input id="password" name="password" type="password" minlength="8" required>
            </div>
            <button class="btn" type="submit">Salvar</button>
        </form>
    </div>
    <?php require __DIR__ . '/../partials/auth_site_footer.php'; ?>
</div>
<script src="/assets/js/app.js" defer></script>
</body>
</html>
