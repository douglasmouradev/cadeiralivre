<?php

declare(strict_types=1);

use App\Helpers\Flash;

/** @var string $title */
/** @var string $content */
/** @var string $csrf */
/** @var string $currentNav */

$flashSuccess = Flash::get('success');
$flashError = Flash::get('error');
$nav = $currentNav ?? 'tenants';
$userName = (string) ($_SESSION['user_name'] ?? '');
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
    <aside class="sidebar" id="admin-sidebar">
        <a href="/saas/tenants" class="brand brand--with-mark">
            <img src="/assets/img/cadeiralivre-logo.png" width="100" height="100" alt="<?= e(app_name()) ?>" class="brand__mark">
        </a>
        <?php if ($userName !== ''): ?>
            <p class="saas-user muted">Olá, <strong><?= e($userName) ?></strong></p>
        <?php endif; ?>
        <nav class="side-nav">
            <a class="<?= $nav === 'tenants' ? 'active' : '' ?>" href="/saas/tenants">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z"/></svg>
                Lojas
            </a>
            <a class="<?= $nav === 'new_tenant' ? 'active' : '' ?>" href="/registrar">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                Nova loja
            </a>
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
