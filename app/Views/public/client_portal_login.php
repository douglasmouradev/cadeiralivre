<?php

declare(strict_types=1);

use App\Helpers\Flash;

/** @var array<string, mixed> $tenant */
/** @var string $slug */
/** @var string $csrf */

$brandHex = tenant_brand_hex((string) ($tenant['primary_color'] ?? ''));
$flashSuccess = Flash::get('success');
$flashError = Flash::get('error');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="<?= e($csrf) ?>">
    <meta name="theme-color" content="<?= e($brandHex) ?>">
    <title><?= e($title) ?></title>
    <?php require __DIR__ . '/../partials/public_tenant_head.php'; ?>
    <link rel="stylesheet" href="<?= e(asset_version('/assets/css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_version('/assets/css/booking.css')) ?>">
</head>
<body class="public-body public-theme booking-premium" style="--tenant-accent: <?= e($brandHex) ?>;">
<main class="public-centered booking-premium__main">
    <header class="store-hero store-hero--compact" style="margin-bottom:1.25rem">
        <div class="store-hero__overlay">
            <div class="store-hero__brand">
                <?php if (!empty($tenant['logo_path'])): ?>
                    <img class="store-hero__logo" src="<?= e(tenant_logo_url($slug)) ?>" alt="">
                <?php endif; ?>
                <div>
                    <p class="store-hero__tagline muted" style="margin:0;font-size:0.85rem">Portal do cliente</p>
                    <h1 class="store-hero__title"><?= e((string) $tenant['name']) ?></h1>
                </div>
            </div>
        </div>
    </header>
    <div class="auth-card">
        <h2 class="mt-0" style="font-family:var(--cl-font-serif,'DM Serif Display',Georgia,serif);font-weight:400;font-size:1.35rem">Entrar como cliente</h2>
        <p class="muted">É necessário ter conta para agendar. A conta fica ativa na hora, sem confirmação por e-mail.</p>
        <?php if (is_string($flashSuccess) && $flashSuccess !== ''): ?>
            <div class="alert alert-success"><?= e($flashSuccess) ?></div>
        <?php endif; ?>
        <?php if (is_string($flashError) && $flashError !== ''): ?>
            <div class="alert alert-error"><?= e($flashError) ?></div>
        <?php endif; ?>
        <form method="post" action="/cliente/<?= e($slug) ?>/entrar" data-validate="1">
            <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
            <div class="row">
                <label for="pe">E-mail</label>
                <input id="pe" name="email" type="email" required autocomplete="email">
            </div>
            <div class="row">
                <label for="pp">Senha</label>
                <input id="pp" name="password" type="password" required autocomplete="current-password" minlength="8">
            </div>
            <button class="btn" type="submit">Entrar e agendar</button>
        </form>
        <p class="muted mt-1"><a href="/cliente/<?= e($slug) ?>/cadastro">Criar conta</a></p>
    </div>
    <?php require __DIR__ . '/../partials/public_platform_footer.php'; ?>
</main>
<script src="<?= e(asset_version('/assets/js/app.js')) ?>" defer></script>
</body>
</html>
