<?php

declare(strict_types=1);

use App\Helpers\Flash;

/** @var string $title */
/** @var string $content */
/** @var string $csrf */
/** @var string $currentNav */

$flashSuccess = Flash::get('success');
$flashError = Flash::get('error');
$nav = $currentNav ?? 'dashboard';
$userName = (string) ($_SESSION['user_name'] ?? '');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="<?= e($csrf ?? \App\Helpers\Csrf::token()) ?>">
    <title><?= e($title ?? 'Plataforma') ?> — <?= e(app_name()) ?></title>
    <?php require __DIR__ . '/../partials/site_favicons.php'; ?>
    <link rel="manifest" href="/manifest.json">
    <link rel="stylesheet" href="<?= e(asset_version('/assets/css/app.css')) ?>">
</head>
<body class="admin-body">
<div class="admin-shell admin-shell--saas">
    <div class="nav-overlay" id="nav-overlay" aria-hidden="true"></div>
    <aside class="sidebar" id="admin-sidebar">
        <a href="/saas" class="brand brand--with-mark">
            <img src="/assets/img/cadeiralivre-logo.png" width="100" height="100" alt="<?= e(app_name()) ?>" class="brand__mark">
        </a>
        <?php if ($userName !== ''): ?>
            <p class="saas-user muted">Olá, <strong><?= e($userName) ?></strong></p>
        <?php endif; ?>
        <nav class="side-nav">
            <a class="<?= $nav === 'dashboard' ? 'active' : '' ?>" href="/saas">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                Visão geral
            </a>
            <a class="<?= $nav === 'tenants' ? 'active' : '' ?>" href="/saas/tenants">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z"/></svg>
                Lojas
            </a>
            <a class="<?= $nav === 'new_tenant' ? 'active' : '' ?>" href="/saas/loja/nova">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                Nova loja
            </a>
            <a class="<?= $nav === 'plans' ? 'active' : '' ?>" href="/saas/planos">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14H7v-4h5v4zm5 0h-3V9h3v8z"/></svg>
                Planos
            </a>
            <a class="<?= $nav === 'settings' ? 'active' : '' ?>" href="/saas/configuracoes">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M19.14 12.94c.04-.31.06-.63.06-.94 0-.31-.02-.63-.06-.94l2.03-1.58a.49.49 0 00.12-.61l-1.92-3.32a.488.488 0 00-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54a.484.484 0 00-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.04.31-.06.63-.06.94s.02.63.06.94l-2.03 1.58a.49.49 0 00-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.5c-1.93 0-3.5-1.57-3.5-3.5s1.57-3.5 3.5-3.5 3.5 1.57 3.5 3.5-1.57 3.5-3.5 3.5z"/></svg>
                Configurações
            </a>
        </nav>
        <a class="logout-link" href="/logout">Sair</a>
    </aside>
    <main class="main-area">
        <header class="topbar topbar--app">
            <button type="button" class="nav-toggle" id="nav-toggle" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Abrir menu de navegação">
                <span class="nav-toggle__bar" aria-hidden="true"></span>
                <span class="nav-toggle__bar" aria-hidden="true"></span>
                <span class="nav-toggle__bar" aria-hidden="true"></span>
            </button>
            <h1 class="topbar__title"><?= e($title ?? '') ?></h1>
        </header>
        <?php if (is_string($flashSuccess) && $flashSuccess !== ''): ?>
            <div class="alert alert-success"><?= e($flashSuccess) ?></div>
        <?php endif; ?>
        <?php if (is_string($flashError) && $flashError !== ''): ?>
            <div class="alert alert-error"><?= e($flashError) ?></div>
        <?php endif; ?>
        <div id="toast-root" class="toast-root" aria-live="polite"></div>
        <?= $content ?>
    </main>
</div>
<script src="/assets/js/app.js" defer></script>
<script src="/assets/js/saas.js" defer></script>
</body>
</html>
