<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $services */

ob_start();
?>
<h2 class="page-title">Novo barbeiro</h2>
<form method="post" action="/barbeiros" data-validate="1" class="card card--compact">
    <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
    <div class="row">
        <label>Nome</label>
        <input name="name" required>
    </div>
    <div class="row">
        <label>E-mail (login)</label>
        <input name="email" type="email" required>
    </div>
    <div class="row">
        <label>Senha</label>
        <input name="password" type="password" minlength="8" required>
    </div>
    <div class="row">
        <label>Telefone</label>
        <input name="phone">
    </div>
    <div class="row">
        <label>Bio</label>
        <textarea name="bio" rows="3"></textarea>
    </div>
    <div class="row">
        <label>Comissão (%)</label>
        <input name="commission_percent" type="number" step="0.01" value="35">
    </div>
    <div class="row checkbox-list">
        <label>Serviços</label>
        <?php foreach ($services as $s): ?>
            <label><input type="checkbox" name="service_ids[]" value="<?= (int) $s['id'] ?>"> <?= e((string) $s['name']) ?></label>
        <?php endforeach; ?>
    </div>
    <button class="btn" type="submit">Salvar</button>
</form>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin.php';
