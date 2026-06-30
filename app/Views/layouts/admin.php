<?php

declare(strict_types=1);

use App\Helpers\Flash;

/** @var string $title */
/** @var string $content */
/** @var string $currentNav */
/** @var string $csrf */

$nav = $currentNav ?? '';
$userRole = $user_role ?? (string) ($_SESSION['user_role'] ?? '');
$brandHome = in_array($userRole, ['owner', 'receptionist', 'superadmin'], true) ? '/painel' : '/agenda';
$flashSuccess = Flash::get('success');
$flashError = Flash::get('error');
$adminTenant = $admin_tenant ?? null;
$sidebarLogoUrl = '/assets/img/cadeiralivre-logo.png';
$sidebarLogoAlt = app_name();
$sidebarLogoClass = 'brand__mark';
if (is_array($adminTenant) && !empty($adminTenant['logo_path']) && !empty($adminTenant['slug'])) {
    $sidebarLogoUrl = tenant_logo_url((string) $adminTenant['slug']);
    $sidebarLogoAlt = (string) $adminTenant['name'];
    $sidebarLogoClass = 'brand__mark brand__mark--tenant';
}
$tenantSlug = is_array($adminTenant) ? (string) ($adminTenant['slug'] ?? '') : '';
$showNewTenantNav = $userRole === 'owner';
$impersonating = saas_impersonating();
if ($impersonating) {
    $brandHome = '/painel';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="<?= e($csrf ?? \App\Helpers\Csrf::token()) ?>">
    <title><?= e($title ?? 'Painel') ?> — <?= e(app_name()) ?></title>
    <?php require __DIR__ . '/../partials/site_favicons.php'; ?>
    <link rel="manifest" href="/manifest.json">
    <link rel="stylesheet" href="<?= e(asset_version('/assets/css/app.css')) ?>">
</head>
<body class="admin-body">
<div class="admin-shell">
    <div class="nav-overlay" id="nav-overlay" aria-hidden="true"></div>
    <aside class="sidebar" id="admin-sidebar">
        <a href="<?= e($brandHome) ?>" class="brand brand--with-mark">
            <img src="<?= e($sidebarLogoUrl) ?>" width="120" height="120" alt="<?= e($sidebarLogoAlt) ?>" class="<?= e($sidebarLogoClass) ?>">
        </a>
        <nav class="side-nav">
            <?php if ($userRole !== 'barber'): ?>
            <a class="<?= $nav === 'dashboard' ? 'active' : '' ?>" href="/painel">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                Painel
            </a>
            <?php endif; ?>
            <a class="<?= $nav === 'schedule' ? 'active' : '' ?>" href="/agenda">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>
                Agenda
            </a>
            <?php if ($userRole !== 'barber'): ?>
            <a class="<?= $nav === 'services' ? 'active' : '' ?>" href="/servicos">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M7.5 5.6L10 7 8.6 4.5 10 2 7.5 3.4 5 2l1.4 2.5L5 7l2.5-1.4zm12 9.8L17 14l1.4 2.5L17 19l2.5-1.4L22 19l-1.4-2.5L22 14l-2.5 1.4zM22 2l-2.5 1.4L17 2l1.4 2.5L17 7l2.5-1.4L22 7l-1.4-2.5zm-7.63 5.29a.996.996 0 00-1.41 0L1.29 18.96a.996.996 0 000 1.41l2.34 2.34c.39.39 1.02.39 1.41 0L16.7 11.05c.39-.39.39-1.02 0-1.41l-4.83-4.83z"/></svg>
                Serviços
            </a>
            <a class="<?= $nav === 'barbers' ? 'active' : '' ?>" href="/barbeiros">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                Profissionais
            </a>
            <a class="<?= $nav === 'clients' ? 'active' : '' ?>" href="/clientes">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M16 4c0-1.11.89-2 2-2s2 .89 2 2-.89 2-2 2-2-.89-2-2zm4 18v-6h2.5l-2.54-7.63A1.5 1.5 0 0018.54 8H17c-.8 0-1.54.37-2.01 1l-2.7 3.6V22h3v-6h2v6h3zM12.5 11.5c.83 0 1.5-.67 1.5-1.5s-.67-1.5-1.5-1.5S11 9.17 11 10s.67 1.5 1.5 1.5zM5.5 6c1.11 0 2-.89 2-2s-.89-2-2-2-2 .89-2 2 .89 2 2 2zm2 16v-7H2v7h5.5zm7.5 0v-4h-4v4H9V10.5c0-.83.67-1.5 1.5-1.5s1.5.67 1.5 1.5V22h3z"/></svg>
                Clientes
            </a>
            <a class="<?= $nav === 'reports' ? 'active' : '' ?>" href="/relatorios">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
                Relatórios
            </a>
            <a class="<?= $nav === 'reviews' ? 'active' : '' ?>" href="/avaliacoes">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                Avaliações
            </a>
            <a class="<?= $nav === 'settings' ? 'active' : '' ?>" href="/configuracoes">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M19.14 12.94c.04-.31.06-.63.06-.94 0-.31-.02-.63-.06-.94l2.03-1.58a.49.49 0 00.12-.61l-1.92-3.32a.488.488 0 00-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54a.484.484 0 00-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.04.31-.06.63-.06.94s.02.63.06.94l-2.03 1.58a.49.49 0 00-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.5c-1.93 0-3.5-1.57-3.5-3.5s1.57-3.5 3.5-3.5 3.5 1.57 3.5 3.5-1.57 3.5-3.5 3.5z"/></svg>
                Configurações
            </a>
            <?php if ($showNewTenantNav): ?>
            <a class="<?= ($nav ?? '') === 'new_tenant' ? 'active' : '' ?>" href="/registrar">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                Nova loja
            </a>
            <?php endif; ?>
            <?php endif; ?>
            <?php if ($impersonating): ?>
            <form method="post" action="/saas/impersonacao/encerrar" class="saas-impersonate-exit">
                <input type="hidden" name="_csrf_token" value="<?= e($csrf ?? \App\Helpers\Csrf::token()) ?>">
                <button type="submit" class="btn secondary saas-impersonate-exit__btn">Voltar à plataforma</button>
            </form>
            <?php endif; ?>
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
        <?php if ($impersonating && is_array($adminTenant)): ?>
            <div class="alert alert-warn saas-impersonate-banner">
                A aceder ao painel de <strong><?= e((string) ($adminTenant['name'] ?? 'loja')) ?></strong> como suporte da plataforma.
                <form method="post" action="/saas/impersonacao/encerrar" class="form-inline saas-impersonate-banner__form">
                    <input type="hidden" name="_csrf_token" value="<?= e($csrf ?? \App\Helpers\Csrf::token()) ?>">
                    <button type="submit" class="btn secondary">Encerrar</button>
                </form>
            </div>
        <?php endif; ?>
        <div id="toast-root" class="toast-root" aria-live="polite"></div>
        <?= $content ?>
    </main>
</div>
<script src="<?= e(asset_version('/assets/js/app.js')) ?>" defer></script>
<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js').catch(() => {});
  });
}
</script>
</body>
</html>
