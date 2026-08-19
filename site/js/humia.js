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

  const copyText = async (text, button) => {
    try {
      await navigator.clipboard.writeText(text);
      if (button) {
        const previous = button.textContent;
        button.textContent = 'Copied';
        setTimeout(() => { button.textContent = previous; }, 1400);
      }
      return true;
    } catch {
      if (button) button.textContent = 'Select code';
      return false;
    }
  };

  if (copyButton && code) {
    copyButton.addEventListener('click', () => copyText(code.textContent.trim(), copyButton));
  }

  const form = document.querySelector('[data-generator-form]');
  const domainInput = document.querySelector('[data-domain]');
  const domainError = document.querySelector('[data-domain-error]');
  const outputEmpty = document.querySelector('[data-output-empty]');
  const outputResults = document.querySelector('[data-output-results]');
  const robotsOutput = document.querySelector('[data-robots-output]');
  const jsonOutput = document.querySelector('[data-json-output]');
  const verifyUrl = document.querySelector('[data-verify-url]');
  const verifyRobotsUrl = document.querySelector('[data-verify-robots-url]');
  const policySummary = document.querySelector('[data-policy-summary]');
  const copyRobots = document.querySelector('[data-copy-robots]');
  const copyJson = document.querySelector('[data-copy-json]');
  const downloadJson = document.querySelector('[data-download-json]');

  const presetValues = {
    light: {
      user_assistance: true,
      search_retrieval: true,
      bulk_crawl: true,
      training: false,
      attribution: true,
      reporting: true
    },
    balanced: {
      user_assistance: true,
      search_retrieval: true,
      bulk_crawl: false,
      training: false,
      attribution: true,
      reporting: true
    },
    strong: {
      user_assistance: true,
      search_retrieval: false,
      bulk_crawl: false,
      training: false,
      attribution: true,
      reporting: false
    }
  };

  const normalizeOrigin = (rawValue) => {
    const value = rawValue.trim();
    if (!value) throw new Error('Enter your website domain.');
    const withScheme = /^[a-z][a-z0-9+.-]*:\/\//i.test(value) ? value : `https://${value}`;
    const url = new URL(withScheme);
    if (!['http:', 'https:'].includes(url.protocol)) throw new Error('Use an http or https website URL.');
    if (!url.hostname || url.username || url.password) throw new Error('Enter a public website origin.');
    if (url.pathname !== '/' || url.search || url.hash) throw new Error('Enter only the site origin, for example https://example.org.');
    return `${url.protocol}//${url.host}`;
  };

  const setPreset = (name) => {
    const preset = presetValues[name];
    if (!form || !preset) return;
    Object.entries(preset).forEach(([field, checked]) => {
      const input = form.elements[field];
      if (input && input.type === 'checkbox') input.checked = checked;
    });
    document.querySelectorAll('.preset-card').forEach((card) => {
      const radio = card.querySelector('input[type="radio"]');
      card.classList.toggle('is-selected', Boolean(radio && radio.checked));
    });
  };

  document.querySelectorAll('input[name="preset"]').forEach((radio) => {
    radio.addEventListener('change', () => setPreset(radio.value));
  });

  const buildPolicy = (origin) => {
    const canonical = `${origin}/`;
    const policy = {
      protocol: 'HUMIA',
      version: '0.3',
      status: 'draft',
      identity: {
        canonical
      },
      access: {
        public_content: 'allow'
      },
      usage: {
        user_assistance: form.elements.user_assistance.checked ? 'allow' : 'deny',
        search_retrieval: form.elements.search_retrieval.checked ? 'allow' : 'deny',
        bulk_crawl: form.elements.bulk_crawl.checked ? 'allow' : 'deny',
        training: form.elements.training.checked ? 'allow' : 'deny'
      },
      attribution: {
        required: form.elements.attribution.checked,
        canonical_url: true
      }
    };
    if (form.elements.reporting.checked) {
      policy.reciprocity = {
        usage_reporting: 'requested'
      };
    }
    return policy;
  };

  const renderGenerator = (origin) => {
    const policyUrl = `${origin}/.well-known/humia.json`;
    const robotsUrl = `${origin}/robots.txt`;
    const robots = `# HUMIA Protocol discovery (experimental)\nHumia: ${policyUrl}`;
    const policy = buildPolicy(origin);
    const json = JSON.stringify(policy, null, 2);

    const humanMeaning = [
      policy.usage.user_assistance === 'allow'
        ? 'AI agents may read public content to help answer a user request.'
        : 'AI agents are not granted permission to use public content for user assistance.',
      policy.usage.search_retrieval === 'allow'
        ? 'AI agents may search, retrieve and reference public content.'
        : 'Search and retrieval use is not granted by this HUMIA policy.',
      policy.usage.bulk_crawl === 'allow'
        ? 'Bulk crawling is allowed.'
        : 'Bulk crawling is not allowed.',
      policy.usage.training === 'allow'
        ? 'Use of the content for model training is allowed.'
        : 'Use of the content for model training is not allowed.',
      policy.attribution.required
        ? 'Source attribution is required and should preserve the canonical URL.'
        : 'Source attribution is not required by this policy.',
      policy.reciprocity?.usage_reporting === 'requested'
        ? 'Usage reporting is requested when the agent supports it.'
        : 'Usage reporting is not requested.'
    ];

    robotsOutput.textContent = robots;
    jsonOutput.textContent = json;
    verifyUrl.textContent = policyUrl;
    verifyRobotsUrl.textContent = robotsUrl;
    policySummary.replaceChildren(...humanMeaning.map((text) => {
      const item = document.createElement('li');
      item.textContent = text;
      return item;
    }));
    outputEmpty.hidden = true;
    outputResults.hidden = false;

    copyRobots.onclick = () => copyText(robots, copyRobots);
    copyJson.onclick = () => copyText(json, copyJson);
    downloadJson.onclick = () => {
      const blob = new Blob([`${json}\n`], { type: 'application/json;charset=utf-8' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = 'humia.json';
      document.body.appendChild(link);
      link.click();
      link.remove();
      setTimeout(() => URL.revokeObjectURL(url), 0);
    };
  };

  if (form && domainInput) {
    setPreset('balanced');
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      domainError.textContent = '';
      domainInput.removeAttribute('aria-invalid');
      try {
        const origin = normalizeOrigin(domainInput.value);
        domainInput.value = origin;
        renderGenerator(origin);
        outputResults.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'nearest' });
      } catch (error) {
        domainError.textContent = error.message;
        domainInput.setAttribute('aria-invalid', 'true');
        domainInput.focus();
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
