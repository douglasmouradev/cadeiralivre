<?php

declare(strict_types=1);

/** @var array<string, int|float> $stats */
/** @var list<array<string, mixed>> $chart */
/** @var list<array<string, mixed>> $upcoming */
/** @var list<string> $alerts */
/** @var array<string, mixed>|null $tenant */

ob_start();
?>
<section class="grid stats-grid">
    <article class="card stat-card">
        <h3>Agendamentos hoje</h3>
        <p class="stat-value"><?= (int) $stats['appts_today'] ?></p>
    </article>
    <article class="card stat-card">
        <h3>Receita do mês</h3>
        <p class="stat-value">R$ <?= e(number_format((float) $stats['revenue_month'], 2, ',', '.')) ?></p>
    </article>
    <article class="card stat-card">
        <h3>Novos clientes (mês)</h3>
        <p class="stat-value"><?= (int) $stats['new_clients_month'] ?></p>
    </article>
    <article class="card stat-card">
        <h3>Avaliação média</h3>
        <p class="stat-value"><?= e(number_format((float) $stats['avg_rating'], 1, ',', '.')) ?> ★</p>
    </article>
</section>

<?php foreach ($alerts as $a): ?>
    <div class="alert alert-warn"><?= e($a) ?></div>
<?php endforeach; ?>

<section class="grid two-col">
    <article class="card">
        <h2>Receita — últimos 30 dias</h2>
        <canvas id="revChart" height="120" aria-label="Gráfico de receita"></canvas>
    </article>
    <article class="card">
        <h2>Próximos de hoje</h2>
        <ul class="list" id="today-list">
            <?php foreach ($upcoming as $u): ?>
                <li>
                    <span class="muted"><?= e(substr((string) $u['start_datetime'], 11, 5)) ?></span>
                    <?= e((string) $u['client_name']) ?> — <?= e((string) $u['service_name']) ?>
                    <span class="pill"><?= e((string) $u['barber_name']) ?></span>
                </li>
            <?php endforeach; ?>
            <?php if ($upcoming === []): ?>
                <li class="empty-state empty-state--inline muted">Nenhum agendamento restante hoje. Configure horários em <a href="/agenda">Agenda</a> para receber reservas online.</li>
            <?php endif; ?>
        </ul>
    </article>
</section>

<script type="application/json" id="chart-data"><?= json_encode($chart, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('revChart');
  const raw = document.getElementById('chart-data');
  if (!el || !raw) return;
  const data = JSON.parse(raw.textContent || '[]');
  const ctx = el.getContext('2d');
  const w = el.width = el.clientWidth;
  const h = el.height;
  ctx.clearRect(0,0,w,h);
  const vals = data.map(r => parseFloat(r.total));
  const max = Math.max(1, ...vals);
  ctx.strokeStyle = getComputedStyle(document.documentElement).getPropertyValue('--accent').trim() || '#7c5e3c';
  ctx.lineWidth = 2;
  ctx.beginPath();
  data.forEach((r, i) => {
    const x = (i / (data.length - 1 || 1)) * (w - 8) + 4;
    const y = h - 8 - (parseFloat(r.total) / max) * (h - 16);
    if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
  });
  ctx.stroke();
  setInterval(async () => {
    try {
      const res = await fetch('/painel/api/hoje', { headers: { 'Accept': 'application/json' } });
      const j = await res.json();
      const ul = document.getElementById('today-list');
      if (!ul || !Array.isArray(j.upcoming)) return;
      ul.innerHTML = '';
      j.upcoming.forEach(u => {
        const li = document.createElement('li');
        li.innerHTML = `<span class="muted">${(u.start_datetime||'').substr(11,5)}</span> ${u.client_name} — ${u.service_name} <span class="pill">${u.barber_name}</span>`;
        ul.appendChild(li);
      });
    } catch (e) {}
  }, 60000);
});
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin.php';
