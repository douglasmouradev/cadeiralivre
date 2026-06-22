<?php

declare(strict_types=1);

/** @var string $title */
/** @var string $csrf */
/** @var string $token */

$authAsideTitle = 'Nova senha, mesmo painel.';
$authAsideLead = 'Escolha uma senha forte com pelo menos 8 caracteres.';
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
    <meta name="theme-color" content="#f7f4ef">
    <link rel="stylesheet" href="<?= e(asset_version('/assets/css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_version('/assets/css/auth.css')) ?>">
</head>
<body class="auth-body">
<div class="auth-shell">
    <?php require __DIR__ . '/../partials/auth_brand_aside.php'; ?>
    <div class="auth-shell__main">
        <div class="auth-page">
            <div class="auth-card auth-card--login">
                <h1>Nova senha</h1>
                <form method="post" action="/redefinir-senha" data-validate="1">
                    <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
                    <input type="hidden" name="token" value="<?= e($token) ?>">
                    <div class="row">
                        <label for="password">Nova senha</label>
                        <input id="password" name="password" type="password" minlength="8" required autocomplete="new-password">
                    </div>
                    <button class="btn" type="submit">Salvar</button>
                </form>
                <p class="muted mt-1 auth-card__links"><a href="/login">Voltar ao login</a></p>
            </div>
        </div>
        <?php require __DIR__ . '/../partials/auth_site_footer.php'; ?>
    </div>
</div>
<script src="<?= e(asset_version('/assets/js/app.js')) ?>" defer></script>
</body>
</html>
