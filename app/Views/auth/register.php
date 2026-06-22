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
    <div class="auth-card auth-card--login">
        <div class="auth-brand">
            <img src="/assets/img/cadeiralivre-logo.png" width="160" height="160" alt="<?= e(app_name()) ?>">
        </div>
        <h1>Criar loja</h1>
        <form method="post" action="/cadastro" data-validate="1">
            <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
            <div class="row">
                <label for="shop_name">Nome da loja</label>
                <input id="shop_name" name="shop_name" required>
            </div>
            <div class="row">
                <label for="slug">Identificador público (URL)</label>
                <input id="slug" name="slug" required pattern="[a-z0-9-]+" title="apenas minúsculas, números e hífen">
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
                <input id="phone" name="phone">
            </div>
            <div class="row">
                <label for="password">Senha (mín. 8)</label>
                <input id="password" name="password" type="password" minlength="8" required>
            </div>
            <button class="btn" type="submit">Cadastrar</button>
        </form>
        <p class="muted mt-1 auth-card__links"><a href="/painel">Voltar ao painel</a></p>
    </div>
    <?php require __DIR__ . '/../partials/auth_site_footer.php'; ?>
</div>
<script src="/assets/js/app.js" defer></script>
</body>
</html>
