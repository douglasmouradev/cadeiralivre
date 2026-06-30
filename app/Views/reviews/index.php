<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $items */
/** @var array{count: int, average: float} $stats */
/** @var string $timezone */

$timezone = is_array($admin_tenant ?? null)
    ? (string) ($admin_tenant['timezone'] ?? 'America/Sao_Paulo')
    : 'America/Sao_Paulo';

ob_start();
?>
<div class="toolbar">
    <h2 class="toolbar__title">Avaliações</h2>
</div>

<div class="card card--compact mb-1">
    <p class="muted" style="margin:0">
        <?php if ($stats['count'] > 0): ?>
            Média <strong><?= e((string) $stats['average']) ?></strong> · <?= (int) $stats['count'] ?> avaliação(ões)
        <?php else: ?>
            Nenhuma avaliação recebida ainda.
        <?php endif; ?>
    </p>
</div>

<?php if ($items === []): ?>
    <div class="card">
        <p class="muted" style="margin:0">Quando clientes avaliarem após o atendimento, as notas aparecem aqui.</p>
    </div>
<?php else: ?>
    <div class="card">
        <div class="my-appointments-table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Serviço</th>
                        <th>Profissional</th>
                        <th>Nota</th>
                        <th>Comentário</th>
                        <th>Visível</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <?php
                        $reviewId = (int) ($item['id'] ?? 0);
                        $isPublic = (int) ($item['is_public'] ?? 0) === 1;
                        $start = (string) ($item['start_datetime'] ?? '');
                        $dtLabel = $start !== '' ? format_datetime_in_tenant_tz($start, $timezone) : '—';
                        ?>
                        <tr>
                            <td><?= e($dtLabel) ?></td>
                            <td><?= e((string) ($item['client_name'] ?? '—')) ?></td>
                            <td><?= e((string) ($item['service_name'] ?? '—')) ?></td>
                            <td><?= e((string) ($item['barber_name'] ?? '—')) ?></td>
                            <td><?= (int) ($item['rating'] ?? 0) ?> ★</td>
                            <td><?= e(trim((string) ($item['comment'] ?? '')) ?: '—') ?></td>
                            <td>
                                <form method="post" action="/avaliacoes/visibilidade" class="form-inline">
                                    <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
                                    <input type="hidden" name="review_id" value="<?= $reviewId ?>">
                                    <input type="hidden" name="is_public" value="<?= $isPublic ? '0' : '1' ?>">
                                    <button type="submit" class="btn secondary"><?= $isPublic ? 'Ocultar' : 'Publicar' ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin.php';
