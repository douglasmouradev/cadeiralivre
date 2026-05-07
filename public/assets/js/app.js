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
})();
