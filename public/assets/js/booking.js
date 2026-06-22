(() => {
  const main = document.querySelector('[data-booking-main]');
  if (!main) return;
  const slug = main.getAttribute('data-booking-slug') || '';
  const steps = document.querySelectorAll('#steps .step');
  const panels = document.querySelectorAll('[data-panel]');
  const serviceHidden = document.getElementById('pub-service');
  const barberHidden = document.getElementById('pub-barber');
  const dateHidden = document.getElementById('pub-date');
  const dateLabel = document.getElementById('date-label');
  const slotList = document.getElementById('slot-list');
  const skel = document.getElementById('slot-skeleton');
  const startHidden = document.getElementById('f-start');
  const slotSection = document.getElementById('slot-section');
  const summaryEl = document.getElementById('booking-summary');
  const calendarEl = document.getElementById('booking-calendar');
  const barberWrap = document.getElementById('barber-select-wrap');
  const barberContinue = document.getElementById('barber-continue');

  let selectedServiceName = '';
  let calendarMonth = new Date();
  calendarMonth.setDate(1);

  const barberMode = () => {
    const el = document.querySelector('input[name="barber_mode_choice"]:checked');
    return el instanceof HTMLInputElement ? el.value : 'one';
  };

  const show = (n) => {
    const step = String(n);
    panels.forEach((p) => {
      p.hidden = p.getAttribute('data-panel') !== step;
    });
    steps.forEach((s) => s.classList.toggle('active', s.getAttribute('data-step') === step));
    if (slotSection) slotSection.setAttribute('aria-busy', step === '4' ? 'true' : 'false');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const updateSummary = () => {
    if (!summaryEl) return;
    const parts = [];
    if (selectedServiceName) parts.push(`<dt>Serviço</dt><dd>${selectedServiceName}</dd>`);
    if (barberMode() === 'any') {
      parts.push('<dt>Profissional</dt><dd>Qualquer disponível</dd>');
    } else if (barberHidden?.value) {
      const card = document.querySelector(`.pick-card--barber[data-barber-id="${barberHidden.value}"] .pick-card__title`);
      parts.push(`<dt>Profissional</dt><dd>${card?.textContent || '—'}</dd>`);
    }
    if (dateHidden?.value) {
      const [y, m, d] = dateHidden.value.split('-');
      parts.push(`<dt>Data</dt><dd>${d}/${m}/${y}</dd>`);
    }
    if (startHidden?.value) {
      parts.push(`<dt>Horário</dt><dd>${startHidden.value.substring(11, 16)}</dd>`);
    }
    summaryEl.innerHTML = parts.length ? `<dl class="booking-summary__list">${parts.join('')}</dl>` : '';
  };

  document.querySelectorAll('.pick-card--service').forEach((card) => {
    card.addEventListener('click', () => {
      document.querySelectorAll('.pick-card--service').forEach((c) => c.classList.remove('is-selected'));
      card.classList.add('is-selected');
      if (serviceHidden) serviceHidden.value = card.getAttribute('data-service-id') || '';
      selectedServiceName = card.getAttribute('data-service-name') || '';
      show(2);
    });
  });

  document.querySelectorAll('.pick-card--barber').forEach((card) => {
    card.addEventListener('click', () => {
      if (barberMode() !== 'one') return;
      document.querySelectorAll('.pick-card--barber').forEach((c) => c.classList.remove('is-selected'));
      card.classList.add('is-selected');
      if (barberHidden) barberHidden.value = card.getAttribute('data-barber-id') || '';
    });
  });

  document.querySelectorAll('[data-next]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const next = btn.getAttribute('data-next');
      if (!next) return;
      if (next === '2' && serviceHidden && !serviceHidden.value) {
        window.App?.toast?.('Escolha um serviço para continuar.', 'warn');
        return;
      }
      if (next === '3') {
        if (barberMode() === 'one' && barberHidden && !barberHidden.value) {
          window.App?.toast?.('Selecione um profissional ou marque “qualquer disponível”.', 'warn');
          return;
        }
      }
      if (next === '4' && dateHidden && !dateHidden.value) {
        window.App?.toast?.('Escolha uma data no calendário.', 'warn');
        return;
      }
      show(next);
    });
  });

  const renderEmpty = (msg) => {
    if (!slotList) return;
    slotList.innerHTML = '';
    const p = document.createElement('p');
    p.className = 'empty-state empty-state--block';
    p.textContent = msg;
    slotList.appendChild(p);
  };

  const loadSlots = async () => {
    if (!serviceHidden || !dateHidden || !slotList || !skel) return;
    if (!serviceHidden.value || !dateHidden.value) {
      window.App?.toast?.('Serviço e data são obrigatórios.', 'warn');
      return;
    }
    slotList.innerHTML = '';
    skel.classList.add('is-visible');
    skel.setAttribute('aria-hidden', 'false');
    const mode = barberMode();
    const params = new URLSearchParams({ service_id: serviceHidden.value, date: dateHidden.value });
    if (mode === 'any') params.set('any', '1');
    else params.set('barber_id', barberHidden ? barberHidden.value : '');
    try {
      const res = await fetch(`/agendar/${encodeURIComponent(slug)}/slots.json?${params.toString()}`, {
        credentials: 'same-origin',
      });
      if (!res.ok) {
        if (res.status === 401) {
          window.location.href = `/cliente/${encodeURIComponent(slug)}/entrar`;
          return;
        }
        if (res.status === 429) {
          renderEmpty('Muitas consultas em pouco tempo. Aguarde um instante e tente de novo.');
          window.App?.toast?.('Aguarde um instante antes de atualizar os horários.', 'warn');
          return;
        }
        throw new Error('http');
      }
      const j = await res.json();
      const list = j.slots || [];
      slotList.innerHTML = '';
      if (list.length === 0) {
        renderEmpty('Nenhum horário disponível nesta data. Tente outro dia ou outro profissional.');
      } else {
        list.forEach((s) => {
          const b = document.createElement('button');
          b.type = 'button';
          b.className = 'slot-chip';
          b.textContent = (s.start || '').substring(11, 16);
          b.addEventListener('click', () => {
            slotList.querySelectorAll('.slot-chip').forEach((x) => x.classList.remove('is-selected'));
            b.classList.add('is-selected');
            if (startHidden) startHidden.value = s.start || '';
            if (s.barber_id && barberHidden) barberHidden.value = String(s.barber_id);
            if (Array.isArray(s.barber_ids) && s.barber_ids.length && barberHidden) {
              barberHidden.value = String(s.barber_ids[0]);
            }
            updateSummary();
            show(5);
          });
          slotList.appendChild(b);
        });
      }
    } catch {
      renderEmpty('Não foi possível carregar os horários. Verifique a conexão e tente de novo.');
      window.App?.toast?.('Erro ao buscar horários.', 'error');
    } finally {
      skel.classList.remove('is-visible');
      skel.setAttribute('aria-hidden', 'true');
    }
  };

  document.querySelectorAll('[data-next="4"]').forEach((btn) => {
    btn.addEventListener('click', () => setTimeout(loadSlots, 0));
  });

  const barberModeRadios = document.querySelectorAll('input[name="barber_mode_choice"]');
  const barberModeHidden = document.getElementById('booking-barber-mode');
  const syncBarberMode = () => {
    const mode = barberMode();
    if (barberModeHidden) barberModeHidden.value = mode;
    if (barberWrap) barberWrap.hidden = mode === 'any';
    if (mode === 'any' && barberHidden) barberHidden.value = barberHidden.value || document.querySelector('.pick-card--barber')?.getAttribute('data-barber-id') || '';
    if (mode === 'one') {
      document.querySelectorAll('.pick-card--barber').forEach((c) => c.classList.remove('is-selected'));
      if (barberHidden) barberHidden.value = '';
    }
  };
  barberModeRadios.forEach((r) => r.addEventListener('change', syncBarberMode));
  syncBarberMode();

  const pad = (n) => String(n).padStart(2, '0');
  const ymd = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const renderCalendar = () => {
    if (!calendarEl) return;
    const year = calendarMonth.getFullYear();
    const month = calendarMonth.getMonth();
    const firstDow = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const monthNames = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    const weekdays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

    let html = '<div class="booking-calendar__head">';
    html += `<button type="button" class="booking-calendar__nav" data-cal-prev aria-label="Mês anterior">‹</button>`;
    html += `<strong>${monthNames[month]} ${year}</strong>`;
    html += `<button type="button" class="booking-calendar__nav" data-cal-next aria-label="Próximo mês">›</button>`;
    html += '</div><div class="booking-calendar__weekdays">';
    weekdays.forEach((w) => { html += `<span>${w}</span>`; });
    html += '</div><div class="booking-calendar__grid">';
    for (let i = 0; i < firstDow; i++) html += '<span class="booking-calendar__pad"></span>';
    for (let day = 1; day <= daysInMonth; day++) {
      const d = new Date(year, month, day);
      const val = ymd(d);
      const isPast = d < today;
      const isSelected = dateHidden?.value === val;
      html += `<button type="button" class="booking-calendar__day${isPast ? ' is-disabled' : ''}${isSelected ? ' is-selected' : ''}" data-date="${val}" ${isPast ? 'disabled' : ''}>${day}</button>`;
    }
    html += '</div>';
    calendarEl.innerHTML = html;

    calendarEl.querySelector('[data-cal-prev]')?.addEventListener('click', () => {
      calendarMonth.setMonth(calendarMonth.getMonth() - 1);
      renderCalendar();
    });
    calendarEl.querySelector('[data-cal-next]')?.addEventListener('click', () => {
      calendarMonth.setMonth(calendarMonth.getMonth() + 1);
      renderCalendar();
    });
    calendarEl.querySelectorAll('.booking-calendar__day:not(.is-disabled)').forEach((btn) => {
      btn.addEventListener('click', () => {
        const val = btn.getAttribute('data-date') || '';
        if (dateHidden) dateHidden.value = val;
        if (dateLabel && val) {
          const [y, m, d] = val.split('-');
          dateLabel.textContent = `Data selecionada: ${d}/${m}/${y}`;
        }
        renderCalendar();
        updateSummary();
      });
    });
  };

  renderCalendar();
  if (dateHidden && !dateHidden.value) {
    dateHidden.value = ymd(today);
    if (dateLabel) {
      dateLabel.textContent = `Data selecionada: ${pad(today.getDate())}/${pad(today.getMonth() + 1)}/${today.getFullYear()}`;
    }
    renderCalendar();
  }

  const form = document.getElementById('booking-form');
  if (form) {
    form.addEventListener('submit', (e) => {
      if (!startHidden || !startHidden.value.trim()) {
        e.preventDefault();
        window.App?.toast?.('Selecione um horário antes de confirmar.', 'warn');
        show(4);
        return;
      }
      if (barberMode() === 'one' && barberHidden && !barberHidden.value) {
        e.preventDefault();
        window.App?.toast?.('Selecione o profissional.', 'warn');
        show(2);
      }
    });
  }
})();
