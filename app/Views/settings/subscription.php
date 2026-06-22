<?php

declare(strict_types=1);

/** @var array<string, mixed>|null $tenant */
/** @var array<string, mixed>|null $plan */
/** @var list<array<string, mixed>> $plans */

ob_start();
$priceFmt = static function (int $cents): string {
    return 'R$ ' . number_format($cents / 100, 2, ',', '.');
};
$checkoutMsg = (string) ($_GET['checkout'] ?? '');
?>
<section class="card mb-1">
    <h3>Plano atual</h3>
    <?php if ($checkoutMsg === 'success'): ?>
        <p class="alert alert-success">Pagamento recebido! A assinatura será atualizada em instantes.</p>
    <?php elseif ($checkoutMsg === 'cancel'): ?>
        <p class="alert alert-error">Checkout cancelado.</p>
    <?php endif; ?>
    <?php if (is_array($plan)): ?>
        <p><strong><?= e((string) $plan['name']) ?></strong> (<?= e((string) $plan['slug']) ?>)</p>
        <p class="muted">Preço de referência mensal: <?= $priceFmt((int) $plan['monthly_price_cents']) ?>.</p>
        <p>Estado da assinatura: <code><?= e((string) ($tenant['subscription_status'] ?? '—')) ?></code></p>
        <?php if (!empty($tenant['trial_ends_at'])): ?>
            <p class="muted">Trial / referência até: <?= e((string) $tenant['trial_ends_at']) ?></p>
        <?php endif; ?>
    <?php else: ?>
        <p class="muted">Plano não definido. Execute as migrations mais recentes.</p>
    <?php endif; ?>
</section>
<section class="card">
    <h3>Assinar ou mudar de plano</h3>
    <p class="muted mb-1">Pagamento seguro via Stripe. Configure <code>STRIPE_SECRET_KEY</code> e <code>stripe_price_id</code> nos planos.</p>
    <div class="landing-plans-grid">
        <?php foreach ($plans as $p):
            $slug = (string) $p['slug'];
            if ($slug === 'free') {
                continue;
            }
            $hasStripe = trim((string) ($p['stripe_price_id'] ?? '')) !== '';
            ?>
            <article class="landing-plan-card">
                <h4><?= e((string) $p['name']) ?></h4>
                <p><?= $priceFmt((int) $p['monthly_price_cents']) ?>/mês</p>
                <?php if ($hasStripe): ?>
                    <form method="post" action="/configuracoes/assinatura/checkout">
                        <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
                        <input type="hidden" name="plan" value="<?= e($slug) ?>">
                        <button type="submit" class="btn">Assinar <?= e((string) $p['name']) ?></button>
                    </form>
                <?php else: ?>
                    <p class="muted">Preço Stripe não configurado.</p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
    <p class="muted mt-1"><a href="/configuracoes">← Voltar às configurações</a></p>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin.php';
