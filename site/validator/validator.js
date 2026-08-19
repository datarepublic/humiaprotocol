(() => {
  const form = document.querySelector('[data-validator-form]');
  const input = document.querySelector('[data-validator-origin]');
  const error = document.querySelector('[data-validator-error]');
  const empty = document.querySelector('[data-validator-empty]');
  const loading = document.querySelector('[data-validator-loading]');
  const results = document.querySelector('[data-validator-results]');
  const summary = document.querySelector('[data-validator-summary]');
  const summaryKicker = document.querySelector('[data-validator-summary-kicker]');
  const summaryTitle = document.querySelector('[data-validator-summary-title]');
  const summaryText = document.querySelector('[data-validator-summary-text]');
  const policyUrl = document.querySelector('[data-validator-policy-url]');
  const checks = document.querySelector('[data-validator-checks]');

  const normalizeOrigin = (rawValue) => {
    const value = rawValue.trim();
    if (!value) throw new Error('Enter a website origin.');
    const withScheme = /^[a-z][a-z0-9+.-]*:\/\//i.test(value) ? value : `https://${value}`;
    const url = new URL(withScheme);
    if (url.protocol !== 'https:') throw new Error('HUMIA v0.3 validation requires HTTPS.');
    if (!url.hostname || url.username || url.password) throw new Error('Enter a public HTTPS website origin.');
    if (url.port && url.port !== '443') throw new Error('Validator V0 supports the standard HTTPS port 443 only.');
    if (url.pathname !== '/' || url.search || url.hash) throw new Error('Enter only the site origin, for example https://example.org.');
    return `https://${url.hostname}`;
  };

  const setBusy = (busy) => {
    loading.hidden = !busy;
    if (busy) {
      empty.hidden = true;
      results.hidden = true;
    }
    const button = form.querySelector('button[type="submit"]');
    button.disabled = busy;
    button.setAttribute('aria-busy', String(busy));
  };

  const renderChecks = (items) => {
    checks.replaceChildren(...items.map((item) => {
      const row = document.createElement('li');
      row.className = `validator-check is-${item.level}`;

      const level = document.createElement('span');
      level.className = 'validator-check-level';
      level.textContent = item.level;

      const body = document.createElement('div');
      const title = document.createElement('strong');
      title.textContent = item.name;
      const message = document.createElement('p');
      message.textContent = item.message;

      body.append(title, message);
      row.append(level, body);
      return row;
    }));
  };

  const renderResult = (data) => {
    summary.classList.remove('is-pass', 'is-error');
    summary.classList.add(data.valid ? 'is-pass' : 'is-error');
    summaryKicker.textContent = data.valid ? 'Compatible' : 'Needs attention';
    summaryTitle.textContent = data.valid
      ? 'HUMIA Protocol v0.3 compatible'
      : 'HUMIA policy needs correction';
    summaryText.textContent = data.valid
      ? 'The required v0.3 checks passed. Warnings and informational checks are shown below.'
      : 'One or more required v0.3 checks failed. Review the errors below before claiming compatibility.';

    policyUrl.textContent = data.policy_url || '';
    policyUrl.href = data.policy_url || '#';
    renderChecks(Array.isArray(data.checks) ? data.checks : []);
    results.hidden = false;
  };

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    error.textContent = '';
    input.removeAttribute('aria-invalid');

    let origin;
    try {
      origin = normalizeOrigin(input.value);
      input.value = origin;
    } catch (err) {
      error.textContent = err.message;
      input.setAttribute('aria-invalid', 'true');
      input.focus();
      return;
    }

    setBusy(true);
    try {
      const response = await fetch('validate.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ origin })
      });

      const data = await response.json().catch(() => null);
      if (!data) throw new Error('Validator returned an unreadable response.');
      if (!response.ok && !Array.isArray(data.checks)) {
        throw new Error(data.error || 'Validator request failed.');
      }

      renderResult(data);
    } catch (err) {
      results.hidden = true;
      empty.hidden = false;
      error.textContent = err.message;
      input.setAttribute('aria-invalid', 'true');
    } finally {
      setBusy(false);
    }
  });
})();
