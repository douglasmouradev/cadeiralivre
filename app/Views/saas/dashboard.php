<?php

declare(strict_types=1);

/** @var array<string, int|float> $stats */
/** @var list<array<string, mixed>> $trialsExpiring */
/** @var list<array<string, mixed>> $pastDueTenants */
/** @var list<array<string, mixed>> $recentLogs */
/** @var string $csrf */

$mrr = (int) ($stats['mrr_cents'] ?? 0);

ob_start();
?>
<section class="grid stats-grid saas-stats">
    <article class="card stat-card">
        <h3>Lojas</h3>
        <p class="stat-value"><?= (int) ($stats['total_tenants'] ?? 0) ?></p>
        <p class="muted stat-sub"><?= (int) ($stats['active_tenants'] ?? 0) ?> ativas · <?= (int) ($stats['suspended_tenants'] ?? 0) ?> suspensas</p>
    </article>
    <article class="card stat-card">
        <h3>MRR estimado</h3>
        <p class="stat-value"><?= e(format_money_cents($mrr)) ?></p>
        <p class="muted stat-sub"><?= (int) ($stats['paying_tenants'] ?? 0) ?> assinaturas ativas</p>
    </article>
    <article class="card stat-card">
        <h3>Agendamentos</h3>
        <p class="stat-value"><?= (int) ($stats['appointments_today'] ?? 0) ?></p>
        <p class="muted stat-sub"><?= (int) ($stats['appointments_month'] ?? 0) ?> no mês</p>
    </article>
    <article class="card stat-card">
        <h3>Novas lojas</h3>
        <p class="stat-value"><?= (int) ($stats['new_tenants_30d'] ?? 0) ?></p>
        <p class="muted stat-sub">últimos 30 dias</p>
    </article>
</section>

<?php if ($trialsExpiring !== [] || $pastDueTenants !== []): ?>
<section class="grid saas-alerts-grid">
    <?php if ($trialsExpiring !== []): ?>
    <article class="card saas-alert-card saas-alert-card--warn">
        <h2>Trial a expirar (7 dias)</h2>
        <ul class="saas-alert-list">
            <?php foreach ($trialsExpiring as $t): ?>
                <li>
                    <a href="/saas/tenants/<?= (int) ($t['id'] ?? 0) ?>"><?= e((string) ($t['name'] ?? '')) ?></a>
                    <span class="muted">até <?= e(format_datetime_in_tenant_tz((string) ($t['trial_ends_at'] ?? ''), 'America/Sao_Paulo')) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>
    <?php endif; ?>
    <?php if ($pastDueTenants !== []): ?>
    <article class="card saas-alert-card saas-alert-card--danger">
        <h2>Pagamento pendente</h2>
        <ul class="saas-alert-list">
            <?php foreach ($pastDueTenants as $t): ?>
                <li>
                    <a href="/saas/tenants/<?= (int) ($t['id'] ?? 0) ?>"><?= e((string) ($t['name'] ?? '')) ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>
    <?php endif; ?>
</section>
<?php endif; ?>

<section class="card">
    <div class="card-header-row">
        <h2>Atividade recente</h2>
        <a class="btn secondary" href="/saas/tenants">Ver todas as lojas</a>
    </div>
    <?php if ($recentLogs === []): ?>
        <p class="empty-state muted">Nenhuma ação registada ainda.</p>
    <?php else: ?>
    <div class="table-scroll">
        <table class="table table--compact">
            <thead>
            <tr>
                <th>Quando</th>
                <th>Quem</th>
                <th>Ação</th>
                <th>Loja</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($recentLogs as $log): ?>
                <tr>
                    <td class="muted"><?= e(format_datetime_in_tenant_tz((string) ($log['created_at'] ?? ''), 'America/Sao_Paulo')) ?></td>
                    <td><?= e((string) ($log['actor_name'] ?? '')) ?></td>
                    <td><code><?= e((string) ($log['action'] ?? '')) ?></code></td>
                    <td>
                        <?php if (!empty($log['tenant_id'])): ?>
                            <a href="/saas/tenants/<?= (int) $log['tenant_id'] ?>"><?= e((string) ($log['tenant_name'] ?? '—')) ?></a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
$currentNav = 'dashboard';
require __DIR__ . '/../layouts/saas.php';
