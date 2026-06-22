(() => {
  const meta = document.querySelector('meta[name="csrf-token"]');
  const csrf = () => (meta && meta.content) || '';

  window.App = {
    csrf,
    toast(msg, type = 'info') {
      const root = document.getElementById('toast-root');
      if (!root) return;
      const el = document.createElement('div');
      const map =
        type === 'success'
          ? 'alert-success'
          : type === 'error'
            ? 'alert-error'
            : type === 'warn'
              ? 'alert-warn'
              : 'alert-info';
      el.className = 'alert ' + map;
      el.setAttribute('role', 'status');
      el.textContent = msg;
      root.appendChild(el);
      setTimeout(() => el.remove(), 4500);
    },
    confirm(message) {
      return window.confirm(message);
    },
    validateRequired(form) {
      let ok = true;
      form.querySelectorAll('[required]').forEach((field) => {
        if (!(field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement || field instanceof HTMLSelectElement)) return;
        if (!field.value.trim()) {
          ok = false;
          field.classList.add('field-error');
        } else {
          field.classList.remove('field-error');
        }
      });
      return ok;
    },
  };

  document.querySelectorAll('form[data-validate="1"]').forEach((form) => {
    form.addEventListener('input', (e) => {
      const t = e.target;
      if (t instanceof HTMLInputElement || t instanceof HTMLTextAreaElement || t instanceof HTMLSelectElement) {
        t.classList.remove('field-error');
      }
    });
    form.addEventListener('submit', (e) => {
      if (form instanceof HTMLFormElement && !window.App.validateRequired(form)) {
        e.preventDefault();
        window.App.toast('Preencha os campos obrigatórios.', 'error');
      }
    });
  });

  const navToggle = document.getElementById('nav-toggle');
  const navOverlay = document.getElementById('nav-overlay');
  if (navToggle && navOverlay) {
    const setOpen = (open) => {
      document.body.classList.toggle('nav-open', open);
      navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      navToggle.setAttribute('aria-label', open ? 'Fechar menu de navegação' : 'Abrir menu de navegação');
      navOverlay.setAttribute('aria-hidden', open ? 'false' : 'true');
    };
    navToggle.addEventListener('click', () => {
      setOpen(!document.body.classList.contains('nav-open'));
    });
    navOverlay.addEventListener('click', () => setOpen(false));
    document.querySelectorAll('.side-nav a, .logout-link').forEach((a) => {
      a.addEventListener('click', () => setOpen(false));
    });
    window.addEventListener('resize', () => {
      if (window.innerWidth > 900) setOpen(false);
    });
  }

  const landingNavToggle = document.getElementById('landing-nav-toggle');
  const landingNavOverlay = document.getElementById('landing-nav-overlay');
  if (landingNavToggle && landingNavOverlay) {
    const setLandingOpen = (open) => {
      document.body.classList.toggle('landing-nav-open', open);
      landingNavToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      landingNavToggle.setAttribute('aria-label', open ? 'Fechar menu' : 'Abrir menu');
      landingNavOverlay.setAttribute('aria-hidden', open ? 'false' : 'true');
    };
    landingNavToggle.addEventListener('click', () => {
      setLandingOpen(!document.body.classList.contains('landing-nav-open'));
    });
    landingNavOverlay.addEventListener('click', () => setLandingOpen(false));
    document.querySelectorAll('#landing-nav a').forEach((a) => {
      a.addEventListener('click', () => setLandingOpen(false));
    });
    window.addEventListener('resize', () => {
      if (window.innerWidth > 768) setLandingOpen(false);
    });
  }

  const polishBarberPickCards = () => {
    const heroLogo = document.querySelector('.store-hero__logo');
    const logoSrc = heroLogo instanceof HTMLImageElement ? heroLogo.src : '';
    document.querySelectorAll('.pick-card--barber').forEach((card) => {
      const desc = card.querySelector('.pick-card__desc');
      if (desc) {
        const text = (desc.textContent || '').trim();
        if (text.startsWith('[')) {
          try {
            const items = JSON.parse(text);
            if (Array.isArray(items) && items.length > 0) {
              const wrap = document.createElement('span');
              wrap.className = 'pick-card__tags';
              items.forEach((item) => {
                const tag = document.createElement('span');
                tag.className = 'pick-card__tag';
                tag.textContent = String(item);
                wrap.appendChild(tag);
              });
              desc.replaceWith(wrap);
            }
          } catch {
            /* mantém texto original */
          }
        }
      }
      if (logoSrc) {
        const ph = card.querySelector('.pick-card__avatar--placeholder');
        if (ph) {
          const img = document.createElement('img');
          img.className = 'pick-card__avatar pick-card__avatar--brand';
          img.src = logoSrc;
          img.width = 56;
          img.height = 56;
          img.alt = '';
          img.loading = 'lazy';
          ph.replaceWith(img);
        }
      }
    });
  };

  if (document.querySelector('.pick-card--barber')) {
    polishBarberPickCards();
  }

  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    if (!(form instanceof HTMLFormElement)) return;
    form.addEventListener('submit', (e) => {
      const msg = form.getAttribute('data-confirm') || 'Confirmar esta ação?';
      if (!window.confirm(msg)) {
        e.preventDefault();
      }
    });
  });
})();
