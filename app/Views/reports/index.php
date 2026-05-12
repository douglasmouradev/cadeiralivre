<?php

declare(strict_types=1);

/** @var array<string, mixed> $data */

ob_start();
$d = $data;
?>
<div class="card card--compact mb-1">
    <form method="get" class="reports-filter">
        <div>
            <label>Período</label>
            <select name="range" onchange="this.form.submit()">
                <?php
                $r = (string) $d['range'];
                foreach (['day' => 'Hoje', 'week' => 'Semana', 'month' => 'Mês', 'custom' => 'Custom'] as $k => $lab) {
                    $sel = $k === $r ? ' selected' : '';
                    echo '<option value="' . e($k) . '"' . $sel . '>' . e($lab) . '</option>';
                }
                ?>
            </select>
        </div>
        <?php if ($r === 'custom'): ?>
            <div><label>Início</label><input type="date" name="start" value="<?= e((string) $d['start']) ?>"></div>
            <div><label>Fim</label><input type="date" name="end" value="<?= e((string) $d['end']) ?>"></div>
            <button class="btn" type="submit">Aplicar</button>
        <?php endif; ?>
    </form>
    <p class="mt-1">
        <a class="btn secondary" href="/relatorios/exportar.csv?range=<?= e($r) ?>&amp;start=<?= e((string) $d['start']) ?>&amp;end=<?= e((string) $d['end']) ?>">Exportar receita por dia (CSV)</a>
    </p>
</div>

<div class="grid two-col">
    <article class="card">
        <h3>Receita por barbeiro</h3>
        <table class="table">
            <?php foreach ($d['byBarber'] as $row): ?>
                <tr><td><?= e((string) $row['barber_name']) ?></td><td>R$ <?= e(number_format((float) $row['total'], 2, ',', '.')) ?></td></tr>
            <?php endforeach; ?>
        </table>
    </article>
    <article class="card">
        <h3>Receita por serviço</h3>
        <table class="table">
            <?php foreach ($d['byService'] as $row): ?>
                <tr><td><?= e((string) $row['service_name']) ?></td><td>R$ <?= e(number_format((float) $row['total'], 2, ',', '.')) ?></td></tr>
            <?php endforeach; ?>
        </table>
    </article>
</div>

<div class="grid two-col">
    <article class="card">
        <h3>Comissões estimadas</h3>
        <table class="table">
            <?php foreach ($d['commissions'] as $row): ?>
                <tr>
                    <td><?= e((string) $row['barber_name']) ?> (<?= e((string) $row['commission_percent']) ?>%)</td>
                    <td>R$ <?= e(number_format((float) $row['commission'], 2, ',', '.')) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </article>
    <article class="card">
        <h3>No-show / cancelamentos</h3>
        <p>No-show: <?= (int) $d['cancellations']['no_show'] ?> (<?= e((string) $d['cancellations']['no_show_rate']) ?>%)</p>
        <p>Cancelados: <?= (int) $d['cancellations']['cancelled'] ?> (<?= e((string) $d['cancellations']['cancel_rate']) ?>%)</p>
    </article>
</div>

<div class="card card--compact">
    <h3>Horários de pico</h3>
    <table class="table">
        <thead><tr><th>Hora</th><th>Agendamentos</th></tr></thead>
        <tbody>
        <?php foreach ($d['peaks'] as $p): ?>
            <tr><td><?= (int) $p['hr'] ?>h</td><td><?= (int) $p['cnt'] ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin.php';
