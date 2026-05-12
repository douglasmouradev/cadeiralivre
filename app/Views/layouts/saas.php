<?php

declare(strict_types=1);

use App\Helpers\Flash;

/** @var string $title */
/** @var string $content */
/** @var string $csrf */

$flashSuccess = Flash::get('success');
$flashError = Flash::get('error');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e($csrf ?? \App\Helpers\Csrf::token()) ?>">
    <title><?= e($title ?? 'Plataforma') ?> — <?= e(app_name()) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="admin-body">
<div class="admin-shell admin-shell--saas">
    <aside class="sidebar">
        <a href="/saas/tenants" class="brand brand--with-mark">
            <img src="/assets/img/cadeiralivre-logo.png" width="100" height="100" alt="<?= e(app_name()) ?>" class="brand__mark">
        </a>
        <nav class="side-nav">
            <a class="active" href="/saas/tenants">Barbearias</a>
            <a href="/registrar">Nova barbearia</a>
        </nav>
        <a class="logout-link" href="/logout">Sair</a>
    </aside>
    <main class="main-area">
        <header class="topbar topbar--app">
            <h1 class="topbar__title"><?= e($title ?? '') ?></h1>
        </header>
        <?php if (is_string($flashSuccess) && $flashSuccess !== ''): ?>
            <div class="alert alert-success"><?= e($flashSuccess) ?></div>
        <?php endif; ?>
        <?php if (is_string($flashError) && $flashError !== ''): ?>
            <div class="alert alert-error"><?= e($flashError) ?></div>
        <?php endif; ?>
        <?= $content ?>
    </main>
</div>
<script src="/assets/js/app.js" defer></script>
</body>
</html>
