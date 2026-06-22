<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $plans */
/** @var array<int, int> $tenantCounts */
/** @var string $csrf */

ob_start();
?>
<p class="muted mb-1">Limites e preços dos planos. O <code>stripe_price_id</code> deve corresponder ao preço criado no painel Stripe.</p>

<?php foreach ($plans as $plan): ?>
    <?php $pid = (int) ($plan['id'] ?? 0); ?>
    <section class="card saas-plan-card mb-1">
        <div class="card-header-row">
            <h2><?= e((string) ($plan['name'] ?? '')) ?> <code class="muted"><?= e((string) ($plan['slug'] ?? '')) ?></code></h2>
            <span class="pill"><?= (int) ($tenantCounts[$pid] ?? 0) ?> lojas</span>
        </div>
        <form method="post" action="/saas/planos/<?= $pid ?>" class="saas-plan-edit-form">
            <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
            <div class="grid saas-plan-fields">
                <div class="row">
                    <label>Nome exibido</label>
                    <input name="name" value="<?= e((string) ($plan['name'] ?? '')) ?>" required>
                </div>
                <div class="row">
                    <label>Preço mensal (centavos)</label>
                    <input name="monthly_price_cents" type="number" min="0" value="<?= (int) ($plan['monthly_price_cents'] ?? 0) ?>">
                </div>
                <div class="row">
                    <label>Máx. profissionais</label>
                    <input name="max_barbers" type="number" min="0" placeholder="Ilimitado" value="<?= $plan['max_barbers'] !== null ? (int) $plan['max_barbers'] : '' ?>">
                </div>
                <div class="row">
                    <label>Máx. agendamentos/mês</label>
                    <input name="max_appointments_per_month" type="number" min="0" placeholder="Ilimitado" value="<?= $plan['max_appointments_per_month'] !== null ? (int) $plan['max_appointments_per_month'] : '' ?>">
                </div>
                <div class="row">
                    <label>Stripe price ID</label>
                    <input name="stripe_price_id" value="<?= e((string) ($plan['stripe_price_id'] ?? '')) ?>" placeholder="price_...">
                </div>
                <div class="row">
                    <label>Ordem</label>
                    <input name="sort_order" type="number" value="<?= (int) ($plan['sort_order'] ?? 0) ?>">
                </div>
            </div>
            <button type="submit" class="btn mt-1">Guardar plano</button>
        </form>
    </section>
<?php endforeach; ?>
<?php
$content = ob_get_clean();
$currentNav = 'plans';
require __DIR__ . '/../layouts/saas.php';
