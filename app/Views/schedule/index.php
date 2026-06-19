<?php

declare(strict_types=1);

/** @var string $mode */
/** @var string $start */
/** @var string $end */
/** @var list<array<string, mixed>> $appointments */
/** @var list<array<string, mixed>> $barbers */
/** @var list<array<string, mixed>> $services */
/** @var list<array<string, mixed>> $clients */
/** @var int|null $barberFilter */

ob_start();
?>
<div class="toolbar toolbar--start">
    <h2 class="toolbar__title">Agenda</h2>
    <a class="btn secondary" href="/agenda?mode=week&date=<?= e($start) ?>">Semana</a>
    <a class="btn secondary" href="/agenda?mode=day&date=<?= e($start) ?>">Dia</a>
    <button class="btn" type="button" id="open-new">Novo agendamento</button>
</div>

<div class="card">
    <table class="table">
        <thead>
        <tr><th>Data/Hora</th><th>Cliente</th><th>Serviço</th><th>Profissional</th><th>Obs.</th><th>Status</th><th>Ações</th></tr>
        </thead>
        <tbody>
        <?php foreach ($appointments as $a): ?>
            <tr>
                <td><?= e((string) $a['start_datetime']) ?></td>
                <td><?= e((string) $a['client_name']) ?></td>
                <td><?= e((string) $a['service_name']) ?></td>
                <td><?= e((string) $a['barber_name']) ?></td>
                <td class="cell-notes"<?php
                    $n = trim((string) ($a['notes'] ?? ''));
                    $tip = $n !== '' ? str_replace(["\r\n", "\n", "\r"], ' · ', $n) : '';
                    if ($tip !== '') {
                        echo ' title="' . e($tip) . '"';
                    }
                ?>><?php
                    echo $n !== '' ? e($n) : '—';
                ?></td>
                <td><?= e((string) $a['status']) ?></td>
                <td class="td-actions">
                    <form method="post" action="/agenda/status" class="form-inline">
                        <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
                        <input type="hidden" name="appointment_id" value="<?= (int) $a['id'] ?>">
                        <input type="hidden" name="status" value="confirmed">
                        <button class="btn secondary" type="submit">Confirmar</button>
                    </form>
                    <form method="post" action="/agenda/status" class="form-inline">
                        <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
                        <input type="hidden" name="appointment_id" value="<?= (int) $a['id'] ?>">
                        <input type="hidden" name="status" value="completed">
                        <button class="btn secondary" type="submit">Concluir</button>
                    </form>
                    <form method="post" action="/agenda/status" class="form-inline" onsubmit="return App.confirm('Cancelar?');">
                        <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
                        <input type="hidden" name="appointment_id" value="<?= (int) $a['id'] ?>">
                        <input type="hidden" name="status" value="cancelled">
                        <input type="hidden" name="cancellation_reason" value="Cancelado pelo painel">
                        <button class="btn danger" type="submit">Cancelar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($appointments === []): ?>
            <tr><td colspan="7" class="muted">Sem agendamentos no período.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="modal-backdrop" id="modal-new" role="dialog" aria-modal="true">
    <div class="modal">
        <h3>Novo agendamento</h3>
        <form method="post" action="/agenda" data-validate="1" id="form-new">
            <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
            <div class="row">
                <label>Cliente existente (ID)</label>
                <select name="client_id">
                    <option value="0">— Novo cliente —</option>
                    <?php foreach ($clients as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"><?= e((string) $c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row">
                <label>Novo cliente — nome</label>
                <input name="new_client_name">
            </div>
            <div class="row">
                <label>Novo cliente — e-mail</label>
                <input name="new_client_email" type="email">
            </div>
            <div class="row">
                <label>Novo cliente — telefone</label>
                <input name="new_client_phone">
            </div>
            <div class="row">
                <label>Serviço</label>
                <select name="service_id" id="f-service" required>
                    <?php foreach ($services as $s): ?>
                        <option value="<?= (int) $s['id'] ?>"><?= e((string) $s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row">
                <label>Profissional</label>
                <select name="barber_id" id="f-barber" required>
                    <?php foreach ($barbers as $b): ?>
                        <?php if ($barberFilter !== null && $barberFilter > 0 && (int) $b['id'] !== $barberFilter) { continue; } ?>
                        <option value="<?= (int) $b['id'] ?>"><?= e((string) $b['user_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row">
                <label>Data</label>
                <input type="date" id="f-date" required value="<?= e(substr($start, 0, 10)) ?>">
            </div>
            <div class="row">
                <label>Horário</label>
                <select name="start_datetime" id="f-slot" required></select>
            </div>
            <div class="row">
                <label>Observações</label>
                <textarea name="notes" rows="2"></textarea>
            </div>
            <button class="btn" type="submit">Salvar</button>
            <button class="btn secondary" type="button" id="close-new">Fechar</button>
        </form>
    </div>
</div>

<script>
(() => {
  const backdrop = document.getElementById('modal-new');
  const open = document.getElementById('open-new');
  const close = document.getElementById('close-new');
  const service = document.getElementById('f-service');
  const barber = document.getElementById('f-barber');
  const date = document.getElementById('f-date');
  const slot = document.getElementById('f-slot');
  const load = async () => {
    slot.innerHTML = '';
    const params = new URLSearchParams({
      service_id: service.value,
      barber_id: barber.value,
      date: date.value
    });
    const res = await fetch('/agenda/slots.json?' + params.toString(), { credentials: 'same-origin' });
    if (!res.ok) {
      const opt = document.createElement('option');
      opt.value = '';
      opt.textContent = res.status === 429 ? 'Aguarde um instante e abra de novo.' : 'Erro ao carregar horários.';
      slot.appendChild(opt);
      return;
    }
    const j = await res.json();
    (j.slots || []).forEach(s => {
      const opt = document.createElement('option');
      opt.value = s.start;
      opt.textContent = (s.start || '').substring(11,16) + ' — ' + (s.start || '').substring(0,10);
      slot.appendChild(opt);
    });
  };
  open?.addEventListener('click', () => { backdrop.classList.add('open'); load(); });
  close?.addEventListener('click', () => backdrop.classList.remove('open'));
  backdrop?.addEventListener('click', (ev) => {
    if (ev.target === backdrop) backdrop.classList.remove('open');
  });
  service?.addEventListener('change', load);
  barber?.addEventListener('change', load);
  date?.addEventListener('change', load);
})();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin.php';
