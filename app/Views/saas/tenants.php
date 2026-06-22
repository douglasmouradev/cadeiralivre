<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $tenants */
/** @var array{q: string, status: string, sub: string, sort: string} $filters */
/** @var string $csrf */

$base = app_base_url();

ob_start();
?>
<section class="card saas-filters-card">
    <form method="get" action="/saas/tenants" class="saas-filters">
        <div class="row">
            <label for="q">Buscar</label>
            <input id="q" name="q" type="search" value="<?= e($filters['q']) ?>" placeholder="Nome, slug, e-mail ou cidade">
        </div>
        <div class="row">
            <label for="status">Estado</label>
            <select id="status" name="status">
                <option value="all"<?= $filters['status'] === 'all' ? ' selected' : '' ?>>Todas</option>
                <option value="active"<?= $filters['status'] === 'active' ? ' selected' : '' ?>>Ativas</option>
                <option value="suspended"<?= $filters['status'] === 'suspended' ? ' selected' : '' ?>>Suspensas</option>
                <option value="trial"<?= $filters['status'] === 'trial' ? ' selected' : '' ?>>Em trial</option>
            </select>
        </div>
        <div class="row">
            <label for="sub">Assinatura</label>
            <select id="sub" name="sub">
                <option value="all"<?= $filters['sub'] === 'all' ? ' selected' : '' ?>>Todas</option>
                <option value="trialing"<?= $filters['sub'] === 'trialing' ? ' selected' : '' ?>>Em trial</option>
                <option value="active"<?= $filters['sub'] === 'active' ? ' selected' : '' ?>>Ativa</option>
                <option value="past_due"<?= $filters['sub'] === 'past_due' ? ' selected' : '' ?>>Pagamento pendente</option>
                <option value="canceled"<?= $filters['sub'] === 'canceled' ? ' selected' : '' ?>>Cancelada</option>
                <option value="none"<?= $filters['sub'] === 'none' ? ' selected' : '' ?>>Sem assinatura</option>
            </select>
        </div>
        <div class="row">
            <label for="sort">Ordenar</label>
            <select id="sort" name="sort">
                <option value="created_desc"<?= $filters['sort'] === 'created_desc' ? ' selected' : '' ?>>Mais recentes</option>
                <option value="created_asc"<?= $filters['sort'] === 'created_asc' ? ' selected' : '' ?>>Mais antigas</option>
                <option value="name"<?= $filters['sort'] === 'name' ? ' selected' : '' ?>>Nome A–Z</option>
                <option value="trial"<?= $filters['sort'] === 'trial' ? ' selected' : '' ?>>Trial a expirar</option>
            </select>
        </div>
        <div class="saas-filters__actions">
            <button type="submit" class="btn">Filtrar</button>
            <a class="btn secondary" href="/saas/tenants">Limpar</a>
            <a class="btn secondary" href="/saas/tenants/exportar">Exportar CSV</a>
        </div>
    </form>
</section>

<section class="grid stats-grid saas-stats">
    <article class="card stat-card">
        <h3>Resultados</h3>
        <p class="stat-value"><?= count($tenants) ?></p>
    </article>
    <article class="card stat-card">
        <h3>Ativas</h3>
        <p class="stat-value"><?= count(array_filter($tenants, static fn ($t) => (string) ($t['status'] ?? '') !== 'suspended')) ?></p>
    </article>
</section>

<section class="card">
    <p class="muted mb-1">Gestão da plataforma <?= e(app_name()) ?>. Suspender uma loja impede agendamentos públicos e o acesso da equipa ao painel.</p>
    <?php if ($tenants === []): ?>
        <p class="empty-state">Nenhuma loja encontrada. <a href="/saas/loja/nova">Criar primeira loja</a>.</p>
    <?php else: ?>
    <div class="table-scroll">
        <table class="table">
            <thead>
            <tr>
                <th>Loja</th>
                <th>Slug</th>
                <th>Cidade</th>
                <th>Estado</th>
                <th>Plano</th>
                <th>Assinatura</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($tenants as $t): ?>
                <?php
                $id = (int) ($t['id'] ?? 0);
                $slug = (string) ($t['slug'] ?? '');
                $publicUrl = $slug !== '' ? $base . '/agendar/' . rawurlencode($slug) : '#';
                $city = trim((string) ($t['city'] ?? '') . ' ' . (string) ($t['state'] ?? ''));
                $sub = (string) ($t['subscription_status'] ?? 'none');
                $subClass = $sub === 'past_due' ? 'pill--danger' : ($sub === 'active' ? 'pill--ok' : '');
                ?>
                <tr>
                    <td><a href="/saas/tenants/<?= $id ?>"><strong><?= e((string) ($t['name'] ?? '')) ?></strong></a></td>
                    <td><code><?= e($slug) ?></code></td>
                    <td><?= e($city !== '' ? $city : '—') ?></td>
                    <td>
                        <?php if ((string) ($t['status'] ?? '') === 'suspended'): ?>
                            <span class="pill pill--danger">Suspensa</span>
                        <?php else: ?>
                            <span class="pill pill--ok">Ativa</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e((string) ($t['plan_label'] ?? $t['plan'] ?? '')) ?></td>
                    <td><span class="pill <?= e($subClass) ?>"><?= e(subscription_status_label($sub)) ?></span></td>
                    <td class="table-actions saas-actions">
                        <a class="btn secondary" href="/saas/tenants/<?= $id ?>">Detalhes</a>
                        <a class="btn secondary" href="<?= e($publicUrl) ?>" target="_blank" rel="noopener">Ver loja</a>
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
$currentNav = 'tenants';
require __DIR__ . '/../layouts/saas.php';
