<?php

declare(strict_types=1);

/** @var array<string, mixed> $barber */
/** @var list<array<string, mixed>> $services */
/** @var list<int> $selectedServices */
/** @var list<array<string, mixed>> $hours */
/** @var list<array<string, mixed>> $blocks */

$specRaw = $barber['specialties'] ?? '[]';
$specs = is_string($specRaw) ? (json_decode($specRaw, true) ?: []) : (array) $specRaw;

ob_start();
?>
<h2 class="page-title">Editar barbeiro</h2>
<form method="post" action="/barbeiros/<?= (int) $barber['id'] ?>" class="card card--compact mb-1">
    <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
    <div class="row">
        <label>Bio</label>
        <textarea name="bio" rows="3"><?= e((string) ($barber['bio'] ?? '')) ?></textarea>
    </div>
    <div class="row">
        <label>Comissão (%)</label>
        <input name="commission_percent" type="number" step="0.01" value="<?= e((string) $barber['commission_percent']) ?>">
    </div>
    <div class="row">
        <label><input type="checkbox" name="is_available" value="1" <?= ((int) $barber['is_available'] === 1) ? 'checked' : '' ?>> Disponível para novos agendamentos</label>
    </div>
    <div class="row">
        <label>Especialidades (uma por linha)</label>
        <textarea name="specialties_text" rows="3"><?= e(implode("\n", array_map(strval(...), $specs))) ?></textarea>
    </div>
    <div class="row checkbox-list">
        <label>Serviços</label>
        <?php foreach ($services as $s): ?>
            <label>
                <input type="checkbox" name="service_ids[]" value="<?= (int) $s['id'] ?>" <?= in_array((int) $s['id'], $selectedServices, true) ? 'checked' : '' ?>>
                <?= e((string) $s['name']) ?>
            </label>
        <?php endforeach; ?>
    </div>
    <button class="btn" type="submit">Salvar</button>
</form>

<h3 class="page-title page-title--section">Horários</h3>
<form method="post" action="/barbeiros/<?= (int) $barber['id'] ?>/horarios" class="card card--compact mb-1">
    <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
    <?php
    $days = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    $byDow = [];
    foreach ($hours as $h) {
        $byDow[(int) $h['day_of_week']] = $h;
    }
    for ($d = 0; $d <= 6; $d++):
        $h = $byDow[$d] ?? ['start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_day_off' => 0];
        ?>
        <div class="form-row-hours">
            <strong><?= e($days[$d]) ?></strong>
            <div>
                <label>Início</label>
                <input type="time" name="start_<?= $d ?>" value="<?= e(substr((string) $h['start_time'], 0, 5)) ?>">
            </div>
            <div>
                <label>Fim</label>
                <input type="time" name="end_<?= $d ?>" value="<?= e(substr((string) $h['end_time'], 0, 5)) ?>">
            </div>
            <label><input type="checkbox" name="off_<?= $d ?>" value="1" <?= ((int) $h['is_day_off'] === 1) ? 'checked' : '' ?>> Folga</label>
        </div>
    <?php endfor; ?>
    <button class="btn" type="submit">Salvar horários</button>
</form>

<h3 class="page-title page-title--section">Bloqueios</h3>
<form method="post" action="/barbeiros/<?= (int) $barber['id'] ?>/bloqueios" class="card card--compact mb-1">
    <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
    <div class="form-row-datetime">
        <div>
            <label>Início</label>
            <input type="datetime-local" name="block_start" required>
        </div>
        <div>
            <label>Fim</label>
            <input type="datetime-local" name="block_end" required>
        </div>
    </div>
    <div class="row">
        <label>Motivo</label>
        <input name="block_reason">
    </div>
    <button class="btn" type="submit">Adicionar bloqueio</button>
</form>

<div class="card card--compact">
    <h3>Bloqueios cadastrados</h3>
    <ul class="list">
        <?php foreach ($blocks as $bl): ?>
            <li class="list-row-between">
                <span><?= e((string) $bl['start_datetime']) ?> → <?= e((string) $bl['end_datetime']) ?> — <?= e((string) ($bl['reason'] ?? '')) ?></span>
                <form method="post" action="/barbeiros/<?= (int) $barber['id'] ?>/bloqueios/<?= (int) $bl['id'] ?>/excluir" class="form-inline" onsubmit="return App.confirm('Remover bloqueio?');">
                    <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
                    <button class="btn danger" type="submit">Excluir</button>
                </form>
            </li>
        <?php endforeach; ?>
        <?php if ($blocks === []): ?>
            <li class="muted">Nenhum bloqueio.</li>
        <?php endif; ?>
    </ul>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin.php';
