<?php

declare(strict_types=1);

$analyticsId = analytics_id();
if ($analyticsId === '') {
    return;
}
$isGa4 = str_starts_with($analyticsId, 'G-');
?>
<?php if ($isGa4): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($analyticsId) ?>"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', <?= json_encode($analyticsId, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
</script>
<?php else: ?>
<script defer data-domain="<?= e($analyticsId) ?>" src="https://plausible.io/js/script.js"></script>
<?php endif; ?>
