<?php

declare(strict_types=1);

$title = $title ?? 'Cliente';
$currentNav = $currentNav ?? 'clients';

/** @var array<string, mixed> $client */
/** @var list<array<string, mixed>> $appointments */

ob_start();
?>
<div class="toolbar">
    <h2 class="toolbar__title"><?= e((string) $client['name']) ?></h2>
    <a class="btn secondary" href="/clientes/<?= (int) $client['id'] ?>/editar">Editar</a>
</div>
<div class="grid two-col">
    <article class="card">
        <h3>Dados</h3>
        <p class="muted">E-mail: <?= e((string) ($client['email'] ?? '—')) ?></p>
        <p class="muted">Telefone: <?= e((string) ($client['phone'] ?? '—')) ?></p>
        <p class="muted">Aniversário: <?= e((string) ($client['birth_date'] ?? '—')) ?></p>
        <p>Notas internas: <?= nl2br(e((string) ($client['notes'] ?? ''))) ?></p>
    </article>
    <article class="card">
        <h3>Histórico</h3>
        <ul class="list">
            <?php foreach ($appointments as $a): ?>
                <li><?= e((string) $a['start_datetime']) ?> — <?= e((string) $a['service_name']) ?> (<?= e((string) $a['status']) ?>)</li>
            <?php endforeach; ?>
            <?php if ($appointments === []): ?>
                <li class="muted">Sem agendamentos.</li>
            <?php endif; ?>
        </ul>
    </article>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin.php';
