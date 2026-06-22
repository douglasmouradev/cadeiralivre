<?php

declare(strict_types=1);

/** @var array<string, mixed> $tenant */
/** @var string $slug */
/** @var array<string, mixed> $appointment */
/** @var int $service_id */
/** @var int $barber_id */
/** @var string $timezone */
/** @var string $csrf */

$brandHex = tenant_brand_hex((string) ($tenant['primary_color'] ?? ''));
$tzId = $timezone;
$apptId = (int) $appointment['id'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e($csrf) ?>">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= e(asset_version()) ?>">
</head>
<body class="public-body public-theme" style="--tenant-accent: <?= e($brandHex) ?>;">
<main class="public-page">
    <header class="public-header">
        <h1 class="public-header__title">Reagendar</h1>
        <p class="muted"><a href="/agendar/<?= e($slug) ?>/meus-agendamentos">← Voltar</a></p>
    </header>
    <div class="card card--compact">
        <p>Atual: <?= e(format_datetime_in_tenant_tz((string) $appointment['start_datetime'], $tzId)) ?></p>
        <form method="post" action="/agendar/<?= e($slug) ?>/meus-agendamentos/<?= $apptId ?>/reagendar" id="reschedule-form">
            <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="start_datetime" id="start_datetime">
            <div class="row">
                <label for="date">Nova data</label>
                <input type="date" id="date" required min="<?= e(date('Y-m-d')) ?>">
            </div>
            <div class="row">
                <label>Horário disponível</label>
                <div id="slot-list" class="slot-pick-grid"></div>
            </div>
            <button type="submit" class="btn" disabled id="btn-submit">Confirmar novo horário</button>
        </form>
    </div>
</main>
<script>
(function () {
  const slug = <?= json_encode($slug) ?>;
  const serviceId = <?= (int) $service_id ?>;
  const barberId = <?= (int) $barber_id ?>;
  const dateInput = document.getElementById('date');
  const slotList = document.getElementById('slot-list');
  const startField = document.getElementById('start_datetime');
  const btn = document.getElementById('btn-submit');
  let picked = '';
  async function loadSlots() {
    const d = dateInput.value;
    if (!d) return;
    slotList.innerHTML = '<p class="muted">Carregando…</p>';
    const url = `/agendar/${encodeURIComponent(slug)}/slots.json?service_id=${serviceId}&barber_id=${barberId}&date=${d}`;
    const res = await fetch(url);
    const data = await res.json();
    const slots = data.slots || [];
    if (!slots.length) {
      slotList.innerHTML = '<p class="muted">Nenhum horário neste dia.</p>';
      return;
    }
    slotList.innerHTML = '';
    slots.forEach((s) => {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'btn secondary';
      b.textContent = s.label || s.start;
      b.dataset.start = s.start;
      b.addEventListener('click', () => {
        picked = s.start;
        startField.value = s.start;
        btn.disabled = false;
        slotList.querySelectorAll('button').forEach((x) => x.classList.remove('active'));
        b.classList.add('active');
      });
      slotList.appendChild(b);
    });
  }
  dateInput.addEventListener('change', loadSlots);
})();
</script>
</body>
</html>
