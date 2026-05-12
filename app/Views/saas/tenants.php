<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $tenants */
/** @var string $csrf */

ob_start();
?>
<section class="card">
    <p class="muted mb-1">Gestão de contas da plataforma. Suspender impede agendamentos públicos e o painel da equipa.</p>
    <div class="table-scroll">
        <table class="table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Slug</th>
                <th>Estado</th>
                <th>Plano</th>
                <th>Assinatura</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($tenants as $t): ?>
                <tr>
                    <td><?= (int) ($t['id'] ?? 0) ?></td>
                    <td><?= e((string) ($t['name'] ?? '')) ?></td>
                    <td><code><?= e((string) ($t['slug'] ?? '')) ?></code></td>
                    <td><?= e((string) ($t['status'] ?? '')) ?></td>
                    <td><?= e((string) ($t['plan_label'] ?? $t['plan'] ?? '')) ?></td>
                    <td><?= e((string) ($t['subscription_status'] ?? '')) ?></td>
                    <td class="table-actions">
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
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/saas.php';
