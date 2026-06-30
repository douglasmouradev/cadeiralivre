<?php

declare(strict_types=1);

/** @var string $appName */
/** @var string $appUrl */
/** @var string $appEnv */
/** @var bool $stripeConfigured */
/** @var bool $mailConfigured */

ob_start();
?>
<section class="card">
    <h2>Plataforma</h2>
    <dl class="detail-list">
        <dt>Nome</dt><dd><?= e($appName) ?></dd>
        <dt>URL</dt><dd><?= e($appUrl !== '' ? $appUrl : '—') ?></dd>
        <dt>Ambiente</dt><dd><code><?= e($appEnv) ?></code></dd>
    </dl>
</section>

<section class="card mt-1">
    <h2>Integrações</h2>
    <dl class="detail-list">
        <dt>Stripe</dt>
        <dd>
            <?php if ($stripeConfigured): ?>
                <span class="pill pill--ok">Configurado</span>
                <p class="muted mt-1">Webhook: <code><?= e($appUrl) ?>/webhooks/stripe</code></p>
            <?php else: ?>
                <span class="pill pill--danger">Não configurado</span>
                <p class="muted mt-1">Defina <code>STRIPE_SECRET_KEY</code> e <code>STRIPE_WEBHOOK_SECRET</code> no servidor.</p>
            <?php endif; ?>
        </dd>
        <dt>E-mail (SMTP)</dt>
        <dd>
            <?php if ($mailConfigured): ?>
                <span class="pill pill--ok">Configurado</span>
            <?php else: ?>
                <span class="pill pill--danger">Não configurado</span>
                <p class="muted mt-1">Defina <code>MAIL_SMTP_HOST</code>, <code>MAIL_FROM_ADDRESS</code> e credenciais no servidor.</p>
            <?php endif; ?>
        </dd>
    </dl>
</section>

<section class="card mt-1">
    <h2>Superadmin</h2>
    <p class="muted">Para criar ou promover um superadmin no servidor:</p>
    <pre class="code-block"><code>php scripts/create_superadmin.php email@dominio.com "SenhaSegura8" "Nome"</code></pre>
</section>
<?php
$content = ob_get_clean();
$currentNav = 'settings';
require __DIR__ . '/../layouts/saas.php';
