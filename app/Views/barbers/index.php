<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $barbers */

ob_start();
?>
<div class="toolbar">
    <h2 class="toolbar__title">Profissionais</h2>
    <a class="btn" href="/barbeiros/novo">Novo</a>
</div>
<div class="card card--table-mobile">
    <div class="table-scroll">
    <table class="table table--stack">
        <thead><tr><th>Nome</th><th>E-mail</th><th>Comissão</th><th>Disponível</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($barbers as $b): ?>
            <tr>
                <td data-label="Nome"><?= e((string) $b['user_name']) ?></td>
                <td data-label="E-mail"><?= e((string) $b['user_email']) ?></td>
                <td data-label="Comissão"><?= e(number_format((float) $b['commission_percent'], 2, ',', '.')) ?>%</td>
                <td data-label="Disponível"><?= ((int) $b['is_available'] === 1) ? 'Sim' : 'Não' ?></td>
                <td class="td-actions" data-label="Ações">
                    <a class="btn secondary" href="/barbeiros/<?= (int) $b['id'] ?>/editar">Editar</a>
                    <form method="post" action="/barbeiros/<?= (int) $b['id'] ?>/disponibilidade">
                        <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
                        <input type="hidden" name="available" value="<?= ((int) $b['is_available'] === 1) ? '0' : '1' ?>">
                        <button class="btn secondary" type="submit"><?= ((int) $b['is_available'] === 1) ? 'Pausar' : 'Ativar' ?></button>
                    </form>
                    <form method="post" action="/barbeiros/<?= (int) $b['id'] ?>/desativar" onsubmit="return App.confirm('Desativar este profissional?');">
                        <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
                        <button class="btn danger" type="submit">Desativar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin.php';
