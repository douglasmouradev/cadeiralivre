<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $services */
/** @var string $title */
/** @var string $currentNav */
/** @var string $csrf */

ob_start();
?>
<div class="toolbar">
    <h2 class="toolbar__title">Serviços</h2>
    <a class="btn" href="/servicos/novo">Novo</a>
</div>
<ul id="service-list" class="card card--flush service-list">
    <?php foreach ($services as $s): ?>
        <li class="service-list__item" draggable="true" data-id="<?= (int) $s['id'] ?>">
            <div>
                <strong><?= e((string) $s['name']) ?></strong>
                <div class="muted"><?= e((string) $s['category']) ?> · <?= (int) $s['duration_minutes'] ?> min · R$ <?= e(number_format((float) $s['price'], 2, ',', '.')) ?></div>
            </div>
            <div class="service-list__actions">
                <span class="pill"><?= ((int) $s['is_active'] === 1) ? 'Ativo' : 'Inativo' ?></span>
                <a class="btn secondary" href="/servicos/<?= (int) $s['id'] ?>/editar">Editar</a>
                <form method="post" action="/servicos/<?= (int) $s['id'] ?>/excluir" class="form-inline" onsubmit="return App.confirm('Excluir este serviço?');">
                    <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
                    <button class="btn danger" type="submit">Excluir</button>
                </form>
            </div>
        </li>
    <?php endforeach; ?>
</ul>
<script>
(() => {
  const ul = document.getElementById('service-list');
  if (!ul) return;
  let dragEl = null;
  ul.querySelectorAll('li[draggable="true"]').forEach(li => {
    li.addEventListener('dragstart', () => { dragEl = li; li.style.opacity = '0.5'; });
    li.addEventListener('dragend', () => { li.style.opacity = '1'; });
    li.addEventListener('dragover', e => { e.preventDefault(); });
    li.addEventListener('drop', e => {
      e.preventDefault();
      if (!dragEl || dragEl === li) return;
      const rect = li.getBoundingClientRect();
      const before = (e.clientY - rect.top) < rect.height / 2;
      ul.insertBefore(dragEl, before ? li : li.nextSibling);
      const order = [...ul.querySelectorAll('li[data-id]')].map(x => x.getAttribute('data-id'));
      fetch('/servicos/ordem', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ _csrf_token: document.querySelector('meta[name="csrf-token"]').content, order })
      }).then(() => App.toast('Ordem salva', 'success')).catch(() => App.toast('Falha ao salvar ordem', 'error'));
    });
  });
})();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin.php';
