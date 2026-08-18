(() => {
  const header = document.querySelector('[data-header]');
  const navToggle = document.querySelector('[data-nav-toggle]');
  const nav = document.querySelector('[data-nav]');
  const copyButton = document.querySelector('[data-copy-code]');
  const code = document.querySelector('[data-manifest-code]');

  const updateHeader = () => {
    if (!header) return;
    header.classList.toggle('is-scrolled', window.scrollY > 12);
  };

  updateHeader();
  window.addEventListener('scroll', updateHeader, { passive: true });

  if (navToggle && nav) {
    navToggle.addEventListener('click', () => {
      const open = nav.classList.toggle('is-open');
      navToggle.setAttribute('aria-expanded', String(open));
    });

    nav.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', () => {
        nav.classList.remove('is-open');
        navToggle.setAttribute('aria-expanded', 'false');
      });
    });
  }

  if (copyButton && code) {
    copyButton.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText(code.textContent.trim());
        const previous = copyButton.textContent;
        copyButton.textContent = 'Copied';
        setTimeout(() => { copyButton.textContent = previous; }, 1400);
      } catch {
        copyButton.textContent = 'Select code';
      }
    });
  }

  const items = [...document.querySelectorAll('.reveal')];
  if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.08, rootMargin: '0px 0px -6% 0px' });

    items.forEach((item) => observer.observe(item));
  } else {
    items.forEach((item) => item.classList.add('is-visible'));
  }
})();
