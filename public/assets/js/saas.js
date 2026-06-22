(() => {
  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    if (!(form instanceof HTMLFormElement)) return;
    form.addEventListener('submit', (e) => {
      const msg = form.getAttribute('data-confirm') || 'Confirmar esta ação?';
      if (!window.confirm(msg)) {
        e.preventDefault();
      }
    });
  });

  document.querySelectorAll('[data-copy]').forEach((btn) => {
    if (!(btn instanceof HTMLButtonElement)) return;
    btn.addEventListener('click', async () => {
      const text = btn.getAttribute('data-copy') || '';
      if (!text) return;
      try {
        await navigator.clipboard.writeText(text);
        if (window.App && typeof window.App.toast === 'function') {
          window.App.toast('Link copiado.', 'success');
        }
      } catch {
        if (window.App && typeof window.App.toast === 'function') {
          window.App.toast('Não foi possível copiar.', 'error');
        }
      }
    });
  });
})();
