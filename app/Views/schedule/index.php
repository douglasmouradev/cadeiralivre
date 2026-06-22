<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;

/** @var string $mode */
/** @var string $start */
/** @var string $end */
/** @var list<array<string, mixed>> $appointments */
/** @var list<array<string, mixed>> $barbers */
/** @var list<array<string, mixed>> $services */
/** @var list<array<string, mixed>> $clients */
/** @var int|null $barberFilter */
/** @var int $availBarberId */
/** @var list<array<string, mixed>> $hours */
/** @var list<array<string, mixed>> $dateOverrides */
/** @var list<array<string, mixed>> $blocks */

$userRole = (string) ($_SESSION['user_role'] ?? '');
$canPickBarber = $userRole !== 'barber' && count($barbers) > 1;
$days = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
$byDow = [];
foreach ($hours as $h) {
    $byDow[(int) $h['day_of_week']] = $h;
}
ob_start();
?>
<div class="toolbar toolbar--start">
    <h2 class="toolbar__title">Agenda</h2>
    <a class="btn secondary" href="/agenda?mode=week&date=<?= e($start) ?>&barber_id=<?= (int) $availBarberId ?>">Semana</a>
    <a class="btn secondary" href="/agenda?mode=day&date=<?= e($start) ?>&barber_id=<?= (int) $availBarberId ?>">Dia</a>
    <button class="btn" type="button" id="open-new">Novo agendamento</button>
    <a class="btn secondary" href="#disponibilidade">Horários disponíveis</a>
</div>

<section id="disponibilidade" class="card card--compact mb-1 availability-panel">
    <h3 class="page-title page-title--section">Horários disponíveis para clientes</h3>
    <p class="muted mb-1">Defina quando os clientes podem agendar online. O horário semanal vale como padrão; use data específica para exceções (feriado, plantão extra ou dia fechado).</p>

    <?php if ($canPickBarber): ?>
        <form method="get" action="/agenda" class="clients-search mb-1">
            <input type="hidden" name="mode" value="<?= e($mode) ?>">
            <input type="hidden" name="date" value="<?= e($start) ?>">
            <label for="avail-barber-pick">Profissional</label>
            <select name="barber_id" id="avail-barber-pick" onchange="this.form.submit()">
                <?php foreach ($barbers as $b): ?>
                    <option value="<?= (int) $b['id'] ?>" <?= (int) $b['id'] === $availBarberId ? 'selected' : '' ?>><?= e((string) $b['user_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    <?php elseif ($availBarberId > 0): ?>
        <?php foreach ($barbers as $b): ?>
            <?php if ((int) $b['id'] === $availBarberId): ?>
                <p class="muted mb-1">Profissional: <strong><?= e((string) $b['user_name']) ?></strong></p>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($availBarberId < 1): ?>
        <p class="alert alert-error">Cadastre um profissional em Profissionais → Novo para liberar horários.</p>
    <?php else: ?>

    <h4>Horário semanal (padrão)</h4>
    <form method="post" action="/agenda/horarios" class="mb-1">
        <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="barber_id" value="<?= (int) $availBarberId ?>">
        <?php for ($d = 0; $d <= 6; $d++):
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
        <button class="btn" type="submit">Salvar horário semanal</button>
    </form>

    <h4>Data específica</h4>
    <form method="post" action="/agenda/data-especifica" class="card card--compact mb-1">
        <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="barber_id" value="<?= (int) $availBarberId ?>">
        <div class="form-row-datetime">
            <div>
                <label for="work_date">Data</label>
                <input type="date" name="work_date" id="work_date" required value="<?= e(date('Y-m-d')) ?>">
            </div>
            <div>
                <label for="date_start">Início</label>
                <input type="time" name="date_start" id="date_start" value="09:00">
            </div>
            <div>
                <label for="date_end">Fim</label>
                <input type="time" name="date_end" id="date_end" value="18:00">
            </div>
        </div>
        <div class="row row--checkbox">
            <label><input type="checkbox" name="date_closed" value="1"> Fechado neste dia (sem agendamentos)</label>
        </div>
        <button class="btn secondary" type="submit">Salvar data específica</button>
    </form>

    <?php if ($dateOverrides !== []): ?>
        <ul class="list mb-1">
            <?php foreach ($dateOverrides as $ov): ?>
                <li class="list-row-between">
                    <span>
                        <?= e((string) $ov['work_date']) ?>
                        <?php if ((bool) $ov['is_closed']): ?>
                            — <em>Fechado</em>
                        <?php else: ?>
                            — <?= e(substr((string) $ov['start_time'], 0, 5)) ?> às <?= e(substr((string) $ov['end_time'], 0, 5)) ?>
                        <?php endif; ?>
                    </span>
                    <form method="post" action="/agenda/data-especifica/<?= (int) $ov['id'] ?>/excluir" class="form-inline" onsubmit="return App.confirm('Remover exceção desta data?');">
                        <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
                        <input type="hidden" name="barber_id" value="<?= (int) $availBarberId ?>">
                        <button class="btn danger" type="submit">Excluir</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <h4>Bloquear horários (pausa, almoço, compromisso)</h4>
    <form method="post" action="/agenda/bloqueios" class="card card--compact mb-1">
        <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="barber_id" value="<?= (int) $availBarberId ?>">
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
            <label>Motivo (opcional)</label>
            <input name="block_reason" placeholder="Ex.: almoço, curso">
        </div>
        <button class="btn secondary" type="submit">Bloquear horário</button>
    </form>

    <?php if ($blocks !== []): ?>
        <ul class="list">
            <?php foreach ($blocks as $bl): ?>
                <li class="list-row-between">
                    <span><?= e((string) $bl['start_datetime']) ?> → <?= e((string) $bl['end_datetime']) ?><?php if (!empty($bl['reason'])): ?> — <?= e((string) $bl['reason']) ?><?php endif; ?></span>
                    <form method="post" action="/agenda/bloqueios/<?= (int) $bl['id'] ?>/excluir" class="form-inline" onsubmit="return App.confirm('Remover bloqueio?');">
                        <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
                        <input type="hidden" name="barber_id" value="<?= (int) $availBarberId ?>">
                        <button class="btn danger" type="submit">Excluir</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="muted">Nenhum bloqueio cadastrado.</p>
    <?php endif; ?>

    <?php endif; ?>
</section>

<div class="appt-list-wrap">
    <h3 class="appt-list__heading page-title page-title--section">Agendamentos do período</h3>
    <?php if ($appointments === []): ?>
        <p class="muted appt-list__empty">Sem agendamentos no período.</p>
    <?php else: ?>
    <div class="appt-list">
        <?php foreach ($appointments as $a):
            $stRaw = (string) ($a['status'] ?? '');
            $st = AppointmentStatus::tryFrom($stRaw);
            $stLabel = $st !== null ? $st->label() : $stRaw;
            $canConfirm = $stRaw === AppointmentStatus::Pending->value;
            $canComplete = in_array($stRaw, [
                AppointmentStatus::Pending->value,
                AppointmentStatus::Confirmed->value,
                AppointmentStatus::InProgress->value,
            ], true);
            $canCancel = $canComplete;
            $canDelete = in_array($stRaw, [
                AppointmentStatus::Cancelled->value,
                AppointmentStatus::NoShow->value,
                AppointmentStatus::Completed->value,
            ], true);
            $apptId = (int) $a['id'];
            $n = trim((string) ($a['notes'] ?? ''));
            ?>
        <article class="appt-card card card--compact">
            <header class="appt-card__head">
                <time class="appt-card__when" datetime="<?= e((string) $a['start_datetime']) ?>"><?= e((string) $a['start_datetime']) ?></time>
                <span class="pill appt-card__status"><?= e($stLabel) ?></span>
            </header>
            <dl class="appt-card__meta">
                <div class="appt-card__row">
                    <dt>Cliente</dt>
                    <dd><?= e((string) $a['client_name']) ?></dd>
                </div>
                <div class="appt-card__row">
                    <dt>Serviço</dt>
                    <dd><?= e((string) $a['service_name']) ?></dd>
                </div>
                <div class="appt-card__row">
                    <dt>Profissional</dt>
                    <dd><?= e((string) $a['barber_name']) ?></dd>
                </div>
                <?php if ($n !== ''): ?>
                <div class="appt-card__row appt-card__row--notes">
                    <dt>Obs.</dt>
                    <dd><?= e($n) ?></dd>
                </div>
                <?php endif; ?>
            </dl>
            <div class="appt-card__actions">
                <?php if ($canConfirm): ?>
                <form method="post" action="/agenda/status" class="form-inline">
                    <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
                    <input type="hidden" name="appointment_id" value="<?= $apptId ?>">
                    <input type="hidden" name="status" value="confirmed">
                    <button class="btn secondary" type="submit">Confirmar</button>
                </form>
                <?php endif; ?>
                <?php if ($canComplete): ?>
                <form method="post" action="/agenda/status" class="form-inline" data-confirm="Marcar este agendamento como concluído?">
                    <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
                    <input type="hidden" name="appointment_id" value="<?= $apptId ?>">
                    <input type="hidden" name="status" value="completed">
                    <button class="btn secondary" type="submit">Concluir</button>
                </form>
                <?php endif; ?>
                <?php if ($canCancel): ?>
                <form method="post" action="/agenda/status" class="form-inline" data-confirm="Cancelar este agendamento?">
                    <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
                    <input type="hidden" name="appointment_id" value="<?= $apptId ?>">
                    <input type="hidden" name="status" value="cancelled">
                    <input type="hidden" name="cancellation_reason" value="Cancelado pelo painel">
                    <button class="btn danger" type="submit">Cancelar</button>
                </form>
                <?php endif; ?>
                <?php if ($canDelete): ?>
                <form method="post" action="/agenda/<?= $apptId ?>/excluir" class="form-inline" data-confirm="Excluir este agendamento permanentemente?">
                    <input type="hidden" name="_csrf_token" value="<?= e($csrf) ?>">
                    <button class="btn danger" type="submit">Excluir</button>
                </form>
                <?php endif; ?>
                <?php if (!$canConfirm && !$canComplete && !$canCancel && !$canDelete): ?>
                    <span class="muted">Sem ações disponíveis</span>
                <?php endif; ?>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
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
