<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $tenants */
/** @var string $csrf */

$base = app_base_url();

ob_start();
?>
<section class="grid stats-grid saas-stats">
    <article class="card stat-card">
        <h3>Lojas cadastradas</h3>
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
        <p class="empty-state">Nenhuma loja cadastrada. <a href="/registrar">Criar primeira loja</a>.</p>
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
                $slug = (string) ($t['slug'] ?? '');
                $publicUrl = $slug !== '' ? $base . '/agendar/' . rawurlencode($slug) : '#';
                $city = trim((string) ($t['city'] ?? '') . ' ' . (string) ($t['state'] ?? ''));
                ?>
                <tr>
                    <td><strong><?= e((string) ($t['name'] ?? '')) ?></strong></td>
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
                    <td><?= e((string) ($t['subscription_status'] ?? '—')) ?></td>
                    <td class="table-actions saas-actions">
                        <a class="btn secondary" href="<?= e($publicUrl) ?>" target="_blank" rel="noopener">Ver loja</a>
                        <?php if ((string) ($t['status'] ?? '') !== 'suspended'): ?>
                            <form method="post" action="/saas/tenants/<?= (int) $t['id'] ?>/suspender" class="form-inline">
                                <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
                                <button type="submit" class="btn secondary danger">Suspender</button>
                            </form>
                        <?php else: ?>
                            <form method="post" action="/saas/tenants/<?= (int) $t['id'] ?>/reativar" class="form-inline">
                                <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
                                <button type="submit" class="btn secondary">Reativar</button>
                            </form>
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
$currentNav = 'tenants';
require __DIR__ . '/../layouts/saas.php';
