<?php

declare(strict_types=1);

/** @var string $title */
/** @var string $description */

$pageTitle = $title ?? 'Página legal';
$pageDesc = $description ?? 'Informações legais do ' . app_name() . '.';
$canonical = app_base_url() . ($_SERVER['REQUEST_URI'] ?? '/');
?>
<meta name="description" content="<?= e($pageDesc) ?>">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($pageTitle) ?> — <?= e(app_name()) ?>">
<meta property="og:description" content="<?= e($pageDesc) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
