<?php

declare(strict_types=1);

/** @var string $title */
/** @var string $csrf */

$authAsideTitle = 'Recupere o acesso em instantes.';
$authAsideLead = 'Enviaremos um link seguro para redefinir sua senha.';
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
                <h1>Recuperar senha</h1>
                <p class="muted" style="margin-top:0">Informe o e-mail da sua conta de loja.</p>
                <form method="post" action="/esqueci-senha" data-validate="1">
                    <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
                    <div class="row">
                        <label for="email">E-mail</label>
                        <input id="email" name="email" type="email" required autocomplete="email">
                    </div>
                    <button class="btn" type="submit">Enviar link</button>
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
