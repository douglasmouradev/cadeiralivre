<?php

declare(strict_types=1);

/** @var array{rows: list<array<string, mixed>>, total: int} $result */
/** @var string $q */
/** @var int $page */
/** @var string $title */
/** @var string $currentNav */
/** @var string $csrf */

/** @var int $totalPages */
/** @var int $perPage */

ob_start();
?>
<div class="toolbar">
    <h2 class="toolbar__title">Clientes</h2>
    <div class="td-actions">
        <a class="btn" href="/clientes/novo">Novo cliente</a>
        <a class="btn secondary" href="/clientes/exportar">Exportar CSV</a>
    </div>
</div>
<form class="card card--compact clients-search" method="get" action="/clientes">
    <input name="q" type="search" value="<?= e($q) ?>" placeholder="Buscar nome, e-mail ou telefone" aria-label="Buscar clientes">
    <button class="btn" type="submit">Buscar</button>
</form>
<div class="card">
    <table class="table">
        <thead><tr><th>Nome</th><th>E-mail</th><th>Telefone</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($result['rows'] as $r): ?>
            <tr>
                <td><?= e((string) $r['name']) ?></td>
                <td><?= e((string) ($r['email'] ?? '')) ?></td>
                <td><?= e((string) ($r['phone'] ?? '')) ?></td>
                <td class="td-actions">
                    <a class="btn secondary" href="/clientes/<?= (int) $r['id'] ?>">Ver</a>
                    <form method="post" action="/clientes/<?= (int) $r['id'] ?>/excluir" class="form-inline" data-confirm="Excluir <?= e((string) $r['name']) ?>?">
                        <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
                        <button type="submit" class="btn secondary danger">Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p class="muted">Total: <?= (int) $result['total'] ?> · Página <?= (int) $page ?> de <?= (int) ($totalPages ?? 1) ?></p>
    <?php if (($totalPages ?? 1) > 1): ?>
        <nav class="pagination" aria-label="Paginação">
            <?php if ($page > 1): ?>
                <a class="btn secondary" href="/clientes?page=<?= $page - 1 ?><?= $q !== '' ? '&q=' . rawurlencode($q) : '' ?>">← Anterior</a>
            <?php endif; ?>
            <?php if ($page < ($totalPages ?? 1)): ?>
                <a class="btn secondary" href="/clientes?page=<?= $page + 1 ?><?= $q !== '' ? '&q=' . rawurlencode($q) : '' ?>">Próxima →</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin.php';
