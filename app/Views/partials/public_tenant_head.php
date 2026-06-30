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
$canonical = app_base_url() . '/agendar/' . rawurlencode($slug);
$storeName = (string) ($tenant['name'] ?? '');
$address = trim((string) ($tenant['address'] ?? ''));
$city = trim((string) ($tenant['city'] ?? ''));
$state = trim((string) ($tenant['state'] ?? ''));
$phone = trim((string) ($tenant['phone'] ?? ''));
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'HairSalon',
    'name' => $storeName,
    'description' => $ogDesc,
    'url' => $canonical,
];
if ($ogImage !== '') {
    $jsonLd['image'] = $ogImage;
}
if ($phone !== '') {
    $jsonLd['telephone'] = $phone;
}
$addrParts = array_filter([$address, $city, $state]);
if ($addrParts !== []) {
    $jsonLd['address'] = [
        '@type' => 'PostalAddress',
        'streetAddress' => $address !== '' ? $address : null,
        'addressLocality' => $city !== '' ? $city : null,
        'addressRegion' => $state !== '' ? $state : null,
        'addressCountry' => 'BR',
    ];
    $jsonLd['address'] = array_filter($jsonLd['address']);
}
?>
<link rel="manifest" href="/manifest.json">
<link rel="canonical" href="<?= e($canonical) ?>">
<?php if (!empty($tenant['logo_path'])): ?>
<link rel="icon" href="<?= e(tenant_logo_url($slug)) ?>" type="image/png">
<?php else: ?>
<?php require __DIR__ . '/site_favicons.php'; ?>
<?php endif; ?>
<meta name="description" content="<?= e($ogDesc) ?>">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($ogTitle) ?>">
<meta property="og:description" content="<?= e($ogDesc) ?>">
<?php if ($ogImage !== ''): ?>
<meta property="og:image" content="<?= e($ogImage) ?>">
<?php endif; ?>
<meta property="og:url" content="<?= e($canonical) ?>">
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
