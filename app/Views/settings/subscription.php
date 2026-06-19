<?php

declare(strict_types=1);

/** @var array<string, mixed>|null $tenant */
/** @var array<string, mixed>|null $plan */
/** @var list<array<string, mixed>> $plans */

ob_start();
$priceFmt = static function (int $cents): string {
    return 'R$ ' . number_format($cents / 100, 2, ',', '.');
};
?>
<section class="card mb-1">
    <h3>Plano atual</h3>
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
    <p class="mt-1">Para cobrança automática, configure <code>STRIPE_SECRET_KEY</code>, preços em Stripe e <code>stripe_price_id</code> na tabela <code>plan_definitions</code>. O webhook <code>/webhooks/stripe</code> atualiza o estado da conta.</p>
</section>
<section class="card">
    <h3>Planos disponíveis</h3>
    <table class="table">
        <thead><tr><th>Nome</th><th>Slug</th><th>Profissionais máx.</th><th>Agend./mês</th><th>Preço</th></tr></thead>
        <tbody>
        <?php foreach ($plans as $p): ?>
            <tr>
                <td><?= e((string) $p['name']) ?></td>
                <td><code><?= e((string) $p['slug']) ?></code></td>
                <td><?= isset($p['max_barbers']) && $p['max_barbers'] !== null ? (int) $p['max_barbers'] : '∞' ?></td>
                <td><?= isset($p['max_appointments_per_month']) && $p['max_appointments_per_month'] !== null ? (int) $p['max_appointments_per_month'] : '∞' ?></td>
                <td><?= $priceFmt((int) $p['monthly_price_cents']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p class="muted mt-1"><a href="/configuracoes">← Voltar às configurações</a></p>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin.php';
