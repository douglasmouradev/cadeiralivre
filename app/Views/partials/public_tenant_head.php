<?php

declare(strict_types=1);

/** @var array<string, mixed> $tenant */
/** @var string $slug */
/** @var string $title */
/** @var string $brandHex */

$ogTitle = (string) ($og_title ?? $title ?? (string) ($tenant['name'] ?? app_name()));
$ogDesc = (string) ($og_description ?? (string) ($tenant['public_tagline'] ?? 'Agende seu horário online.'));
$ogImage = (string) ($og_image ?? '');
if ($ogImage === '' && !empty($tenant['logo_path'])) {
    $ogImage = app_base_url() . tenant_logo_url($slug);
}
$favicon = !empty($tenant['logo_path']) ? tenant_logo_url($slug) : '/assets/img/cadeiralivre-logo.png';
?>
<link rel="icon" href="<?= e($favicon) ?>" type="image/png">
<meta name="description" content="<?= e($ogDesc) ?>">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($ogTitle) ?>">
<meta property="og:description" content="<?= e($ogDesc) ?>">
<?php if ($ogImage !== ''): ?>
<meta property="og:image" content="<?= e($ogImage) ?>">
<?php endif; ?>
<meta property="og:url" content="<?= e(app_base_url() . '/agendar/' . rawurlencode($slug)) ?>">
