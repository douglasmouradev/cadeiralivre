<?php

declare(strict_types=1);

/** @var string $title */
/** @var string $csrf */

$authAsideTitle = 'Abra sua loja em minutos.';
$authAsideLead = 'Cadastre serviços, profissionais e compartilhe seu link de agendamento. Trial de 14 dias, sem cartão.';
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
                <h1>Criar loja</h1>
                <p class="muted" style="margin-top:0">Seu painel e link público ficam prontos na hora.</p>
                <form method="post" action="/cadastro" data-validate="1">
                    <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
                    <div class="row">
                        <label for="shop_name">Nome da loja</label>
                        <input id="shop_name" name="shop_name" required>
                    </div>
                    <div class="row">
                        <label for="slug">Identificador público (URL)</label>
                        <input id="slug" name="slug" required pattern="[a-z0-9-]+" title="apenas minúsculas, números e hífen" placeholder="minha-barbearia">
                    </div>
                    <div class="row">
                        <label for="owner_name">Seu nome</label>
                        <input id="owner_name" name="owner_name" required>
                    </div>
                    <div class="row">
                        <label for="email">E-mail</label>
                        <input id="email" name="email" type="email" required>
                    </div>
                    <div class="row">
                        <label for="phone">Telefone</label>
                        <input id="phone" name="phone" type="tel" autocomplete="tel">
                    </div>
                    <div class="row">
                        <label for="password">Senha (mín. 8)</label>
                        <input id="password" name="password" type="password" minlength="8" required>
                    </div>
                    <button class="btn" type="submit">Cadastrar e começar</button>
                </form>
                <p class="muted mt-1 auth-card__links"><a href="/login">Já tenho conta</a></p>
            </div>
        </div>
        <?php require __DIR__ . '/../partials/auth_site_footer.php'; ?>
    </div>
</div>
<script src="<?= e(asset_version('/assets/js/app.js')) ?>" defer></script>
</body>
</html>
