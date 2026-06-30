(() => {
  if (!document.body.classList.contains('landing-body')) {
    return;
  }

  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const header = document.querySelector('.landing-header');

  const onScroll = () => {
    if (header instanceof HTMLElement) {
      header.classList.toggle('is-scrolled', window.scrollY > 20);
    }
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  const revealEls = document.querySelectorAll('[data-reveal]');
  if (reduced) {
    revealEls.forEach((el) => el.classList.add('is-revealed'));
  } else {
    revealEls.forEach((el, index) => {
      el.style.setProperty('--reveal-delay', `${Math.min(index % 8, 7) * 70}ms`);
    });
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) {
            return;
          }
          entry.target.classList.add('is-revealed');
          observer.unobserve(entry.target);
        });
      },
      { threshold: 0.1, rootMargin: '0px 0px -32px 0px' }
    );
    revealEls.forEach((el) => observer.observe(el));
  }

  if (!reduced) {
    const slots = document.querySelectorAll('.landing-preview__slots li');
    if (slots.length > 0) {
      let active = 0;
      slots[0]?.classList.add('is-pulse');
      window.setInterval(() => {
        slots.forEach((slot) => slot.classList.remove('is-pulse'));
        active = (active + 1) % slots.length;
        slots[active]?.classList.add('is-pulse');
      }, 2400);
    }
  }

  const track = (name, params = {}) => {
    if (typeof gtag === 'function') {
      gtag('event', name, params);
    }
  };

  document.querySelectorAll('a[href="/cadastro"], .landing-btn-primary').forEach((el) => {
    el.addEventListener('click', () => track('cta_signup', { location: 'landing' }));
  });
  document.querySelector('.landing-preview__link')?.addEventListener('click', () => {
    track('demo_click', { location: 'hero_mockup' });
  });
  document.querySelectorAll('.landing-plans .btn, .landing-plans a.btn').forEach((btn) => {
    btn.addEventListener('click', () => track('plan_cta', { location: 'pricing' }));
  });
})();
