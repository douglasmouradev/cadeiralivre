(() => {
  const main = document.querySelector('[data-booking-main]');
  if (!main) return;
  const slug = main.getAttribute('data-booking-slug') || '';
  const steps = document.querySelectorAll('#steps .step');
  const panels = document.querySelectorAll('[data-panel]');
  const service = document.getElementById('pub-service');
  const barber = document.getElementById('pub-barber');
  const date = document.getElementById('pub-date');
  const slotList = document.getElementById('slot-list');
  const skel = document.getElementById('slot-skeleton');
  const startHidden = document.getElementById('f-start');
  const slotSection = document.getElementById('slot-section');

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
  };

  document.querySelectorAll('[data-next]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const next = btn.getAttribute('data-next');
      if (!next) return;
      if (next === '2' && service && !service.value) {
        window.App?.toast?.('Escolha um serviço para continuar.', 'warn');
        return;
      }
      if (next === '3') {
        if (barberMode() === 'one' && barber && !barber.value) {
          window.App?.toast?.('Selecione um barbeiro ou marque “qualquer disponível”.', 'warn');
          return;
        }
      }
      if (next === '4' && date && !date.value) {
        window.App?.toast?.('Escolha uma data.', 'warn');
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
    if (!service || !date || !slotList || !skel) return;
    if (!service.value || !date.value) {
      window.App?.toast?.('Serviço e data são obrigatórios.', 'warn');
      return;
    }
    slotList.innerHTML = '';
    skel.classList.add('is-visible');
    skel.setAttribute('aria-hidden', 'false');
    const mode = barberMode();
    const params = new URLSearchParams({ service_id: service.value, date: date.value });
    if (mode === 'any') params.set('any', '1');
    else params.set('barber_id', barber ? barber.value : '');
    try {
      const res = await fetch(`/agendar/${encodeURIComponent(slug)}/slots.json?${params.toString()}`, {
        credentials: 'same-origin',
      });
      if (!res.ok) {
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
          b.className = 'btn secondary';
          const label = (s.start || '').substring(11, 16);
          b.textContent = label;
          b.addEventListener('click', () => {
            slotList.querySelectorAll('.btn.secondary').forEach((x) => x.classList.remove('is-selected'));
            b.classList.add('is-selected');
            if (startHidden) startHidden.value = s.start || '';
            if (s.barber_id) {
              const sel = document.getElementById('pub-barber');
              if (sel) sel.value = String(s.barber_id);
            }
            if (Array.isArray(s.barber_ids) && s.barber_ids.length) {
              const sel = document.getElementById('pub-barber');
              if (sel) sel.value = String(s.barber_ids[0]);
            }
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
    btn.addEventListener('click', () => {
      setTimeout(loadSlots, 0);
    });
  });

  const barberModeRadios = document.querySelectorAll('input[name="barber_mode_choice"]');
  const barberWrap = document.getElementById('barber-select-wrap');
  const barberModeHidden = document.getElementById('booking-barber-mode');
  const syncBarberModeHidden = () => {
    if (barberModeHidden) barberModeHidden.value = barberMode();
  };
  barberModeRadios.forEach((r) => {
    r.addEventListener('change', () => {
      const v = r.value;
      if (barberWrap) barberWrap.hidden = v === 'any';
      if (barber && v === 'any') barber.removeAttribute('required');
      if (barber && v === 'one') barber.setAttribute('required', 'required');
      syncBarberModeHidden();
    });
  });
  const firstMode = document.querySelector('input[name="barber_mode_choice"]:checked');
  if (firstMode && firstMode.value === 'any' && barberWrap) barberWrap.hidden = true;
  syncBarberModeHidden();

  const form = document.getElementById('booking-form');
  if (form) {
    form.addEventListener('submit', (e) => {
      if (!startHidden || !startHidden.value.trim()) {
        e.preventDefault();
        window.App?.toast?.('Selecione um horário antes de confirmar.', 'warn');
        show(4);
        return;
      }
      if (barber && !barber.value) {
        if (barberMode() === 'one') {
          e.preventDefault();
          window.App?.toast?.('Selecione o barbeiro.', 'warn');
          show(2);
        }
      }
    });
  }

  const today = new Date().toISOString().slice(0, 10);
  if (date && !date.value) date.value = today;
})();
