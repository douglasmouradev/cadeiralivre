<?php

declare(strict_types=1);

$title = $title ?? 'Editar cliente';
$currentNav = $currentNav ?? 'clients';

/** @var array<string, mixed> $client */
/** @var bool $isNew */

$isNew = $isNew ?? false;
$title = $title ?? ($isNew ? 'Novo cliente' : 'Editar cliente');

ob_start();
?>
<h2 class="page-title"><?= e($isNew ? 'Novo cliente' : 'Editar cliente') ?></h2>
<form method="post" action="<?= $isNew ? '/clientes' : '/clientes/' . (int) $client['id'] ?>" data-validate="1" class="card card--compact">
    <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
    <div class="row">
        <label>Nome</label>
        <input name="name" required value="<?= e((string) $client['name']) ?>">
    </div>
    <div class="row">
        <label>E-mail</label>
        <input name="email" type="email" value="<?= e((string) ($client['email'] ?? '')) ?>">
    </div>
    <div class="row">
        <label>Telefone</label>
        <input name="phone" value="<?= e((string) ($client['phone'] ?? '')) ?>">
    </div>
    <div class="row">
        <label>Aniversário</label>
        <input name="birth_date" type="date" value="<?= e((string) ($client['birth_date'] ?? '')) ?>">
    </div>
    <div class="row">
        <label>Notas</label>
        <textarea name="notes" rows="4"><?= e((string) ($client['notes'] ?? '')) ?></textarea>
    </div>
    <button class="btn" type="submit"><?= $isNew ? 'Cadastrar' : 'Salvar' ?></button>
    <?php if (!$isNew): ?>
        <a class="btn secondary" href="/clientes/<?= (int) $client['id'] ?>">Cancelar</a>
    <?php endif; ?>
</form>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin.php';
