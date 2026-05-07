<?php

declare(strict_types=1);

/** @var array<string, mixed>|null $service */
/** @var string $title */
/** @var string $currentNav */
/** @var string $csrf */

$isEdit = $service !== null;
ob_start();
?>
<form method="post" action="<?= $isEdit ? '/servicos/' . (int) $service['id'] : '/servicos' ?>" data-validate="1" class="card card--compact">
    <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
    <div class="row">
        <label>Nome</label>
        <input name="name" required value="<?= e((string) ($service['name'] ?? '')) ?>">
    </div>
    <div class="row">
        <label>Descrição</label>
        <textarea name="description" rows="3"><?= e((string) ($service['description'] ?? '')) ?></textarea>
    </div>
    <div class="grid-cols-3 mb-1">
        <div>
            <label>Duração (min)</label>
            <input name="duration_minutes" type="number" min="5" required value="<?= (int) ($service['duration_minutes'] ?? 30) ?>">
        </div>
        <div>
            <label>Preço</label>
            <input name="price" type="number" step="0.01" min="0" required value="<?= e((string) ($service['price'] ?? '0')) ?>">
        </div>
        <div>
            <label>Ordem</label>
            <input name="display_order" type="number" value="<?= (int) ($service['display_order'] ?? 0) ?>">
        </div>
    </div>
    <div class="row">
        <label>Categoria</label>
        <select name="category">
            <?php
            $cat = (string) ($service['category'] ?? 'Corte');
            $opts = ['Corte', 'Barba', 'Tratamento', 'Combo'];
            foreach ($opts as $o) {
                $sel = $o === $cat ? ' selected' : '';
                echo '<option' . $sel . '>' . e($o) . '</option>';
            }
            ?>
        </select>
    </div>
    <div class="row">
        <label><input type="checkbox" name="is_active" value="1" <?= ((int) ($service['is_active'] ?? 1) === 1) ? 'checked' : '' ?>> Ativo</label>
    </div>
    <button class="btn" type="submit">Salvar</button>
</form>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin.php';
