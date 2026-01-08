/* WatchNexus v3 - settings.js (FULL FILE)
 * - Hot-swap safe (delegated events)
 * - Loads user module toggles (settings_get.php)
 * - Loads integrations (integrations_get.php), saves & tests
 * - Admin-only global module policy editor (admin_modules_get/save.php)
 * - DEBUG: If an API returns non-JSON, shows URL + first 400 chars on page + console
 */

(() => {
  const byId = (id) => document.getElementById(id);

  // Proof it loaded
  window.__WNX_SETTINGS_JS = 'loaded';

  function pageIsSettings() {
    const app = byId('appContent');
    return app && (app.getAttribute('data-page') === 'settings');
  }

  async function fetchJSON(url, opts) {
    const res = await fetch(url, opts || { credentials: 'same-origin' });
    const txt = await res.text();
    try {
      return JSON.parse(txt);
    } catch {
      const head = txt.slice(0, 400);
      console.error('[WNX] Bad JSON from:', url, 'HTTP', res.status, 'HEAD:', head);
      return {
        ok: false,
        error: 'Bad JSON from server',
        url,
        status: res.status,
        head
      };
    }
  }

  function checkbox(id, label, checked, disabled = false, note = '') {
    return `
      <label class="row" style="gap:10px;align-items:center;margin:8px 0;">
        <input type="checkbox" data-id="${id}" ${checked ? 'checked' : ''} ${disabled ? 'disabled' : ''}>
        <span>${label}</span>
        ${note ? `<span class="small muted" style="margin-left:auto;">${note}</span>` : ''}
      </label>
    `;
  }

  function inputRow(label, id, value = '', placeholder = '', type='text') {
    return `
      <label class="small muted" for="${id}" style="display:block;margin-top:10px;">${label}</label>
      <input class="input" id="${id}" type="${type}" value="${String(value ?? '')}" placeholder="${placeholder}"
             style="width:100%;padding:10px;border-radius:12px;border:1px solid rgba(255,255,255,0.14);background:rgba(255,255,255,0.06);color:inherit;">
    `;
  }

  function selectRow(label, id, value, options) {
    const opts = options.map(o => `<option value="${o}" ${o===value?'selected':''}>${o}</option>`).join('');
    return `
      <label class="small muted" for="${id}" style="display:block;margin-top:10px;">${label}</label>
      <select class="input" id="${id}" style="width:100%;padding:10px;border-radius:12px;border:1px solid rgba(255,255,255,0.14);background:rgba(255,255,255,0.06);color:inherit;">
        ${opts}
      </select>
    `;
  }

  function card(title, bodyHtml, footerHtml='') {
    return `
      <div class="card" style="margin-top:14px;">
        <div class="hd"><h2>${title}</h2><div class="spacer"></div></div>
        <div class="bd">${bodyHtml}</div>
        ${footerHtml ? `<div class="bd" style="border-top:1px solid rgba(255,255,255,0.10);">${footerHtml}</div>` : ''}
      </div>
    `;
  }

  // -------------------- INTEGRATIONS UI --------------------

  function renderIntegrationCard(type, data) {
    const enabled = !!data.enabled;
    const base = data.base_url || '';
    const variant = data.api_variant || 'auto';
    const last = data.last_test || {};
    const status = last.status || 'never';
    const msg = last.message || '';

    const hasApiKey = !!(data.secrets && data.secrets.api_key);
    const hasUser = !!(data.secrets && data.secrets.username);
    const hasPass = !!(data.secrets && data.secrets.password);

    const commonTop = `
      ${checkbox(`int_${type}_enabled`, `Enable ${type}`, enabled)}
      ${selectRow('API Variant', `int_${type}_variant`, variant, ['auto','v1','v2'])}
      ${inputRow('Base URL', `int_${type}_base`, base, 'http://localhost:9117 (Jackett) / http://localhost:9696 (Prowlarr)')}
    `;

    let secretFields = '';
    if (type === 'jackett' || type === 'prowlarr') {
      secretFields = `
        ${inputRow('API Key', `int_${type}_apikey`, '', hasApiKey ? 'Stored (leave blank to keep)' : 'Paste API key', 'password')}
        <p class="small muted" style="margin-top:10px;">API key is encrypted at rest. Leave blank to keep existing.</p>
      `;
    } else if (type === 'seedr') {
      secretFields = `
        ${inputRow('Username / Email', `int_${type}_user`, '', hasUser ? 'Stored (leave blank to keep)' : 'Email / username', 'text')}
        ${inputRow('Password', `int_${type}_pass`, '', hasPass ? 'Stored (leave blank to keep)' : 'Password', 'password')}
        <p class="small muted" style="margin-top:10px;">Credentials are encrypted at rest. Leave blank to keep existing.</p>
      `;
    } else if (type === 'trakt') {
      secretFields = `
        ${inputRow('Client ID', `int_${type}_cid`, '', 'Paste client id', 'text')}
        ${inputRow('Client Secret', `int_${type}_csecret`, '', 'Paste client secret', 'password')}
        <p class="small muted" style="margin-top:10px;">OAuth token flow comes next. These will be encrypted at rest.</p>
      `;
    }

    const footer = `
      <div class="row" style="gap:10px;align-items:center;">
        <button class="btn primary" data-int-save="${type}" type="button">Save</button>
        <button class="btn" data-int-test="${type}" type="button">Test</button>
        <span class="small muted" style="margin-left:auto;">Last test: <strong>${status}</strong>${msg ? ' — ' + msg : ''}</span>
      </div>
    `;

    return card(type.toUpperCase(), commonTop + secretFields, footer);
  }

  async function saveIntegration(type) {
    const status = byId('settingsStatus');
    if (status) status.textContent = `Saving ${type}…`;

    const enabled = !!byId(`int_${type}_enabled`)?.checked;
    const api_variant = byId(`int_${type}_variant`)?.value || 'auto';
    const base_url = byId(`int_${type}_base`)?.value || '';

    const secrets = {};
    if (type === 'jackett' || type === 'prowlarr') {
      const k = byId(`int_${type}_apikey`)?.value || '';
      if (k.trim().length) secrets.api_key = k.trim();
    } else if (type === 'seedr') {
      const u = byId(`int_${type}_user`)?.value || '';
      const p = byId(`int_${type}_pass`)?.value || '';
      if (u.trim().length) secrets.username = u.trim();
      if (p.length) secrets.password = p;
    } else if (type === 'trakt') {
      const cid = byId(`int_${type}_cid`)?.value || '';
      const cs = byId(`int_${type}_csecret`)?.value || '';
      if (cid.trim().length) secrets.client_id = cid.trim();
      if (cs.length) secrets.client_secret = cs;
    }

    const payload = { integration_type: type, enabled, api_variant, base_url, config: {}, secrets };

    const json = await fetchJSON('/api/integrations_save.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    if (!json.ok) {
      if (status) status.textContent = `Save failed (${type}): ${json.error || json.detail || 'unknown'}${json.url ? ' @ ' + json.url : ''}${json.head ? ' | ' + json.head : ''}`;
      return;
    }

    if (status) status.textContent = `Saved ${type}. Reloading…`;
    await loadSettings();
  }

  async function testIntegration(type) {
    const status = byId('settingsStatus');
    if (status) status.textContent = `Testing ${type}…`;

    const json = await fetchJSON('/api/integrations_test.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ integration_type: type })
    });

    if (!json.ok) {
      if (status) status.textContent = `Test failed (${type}): ${json.error || 'unknown'}${json.url ? ' @ ' + json.url : ''}${json.head ? ' | ' + json.head : ''}`;
      return;
    }

    if (status) status.textContent = `Test complete (${type}). Reloading…`;
    await loadSettings();
  }

  // -------------------- ADMIN MODULE POLICY UI --------------------

  function renderAdminPolicyCard(forcedMap) {
    const forced = forcedMap || {};
    const mods = [
      ['calendar','Calendar'],
      ['browse','Browse'],
      ['myshows','My Shows'],
      ['settings','Settings'],
      ['themes','Themes'],
      ['trakt','Trakt'],
      ['seedr','Seedr'],
      ['jackett','Jackett'],
      ['prowlarr','Prowlarr'],
      ['mod','Mod Tools'],
      ['admin','Admin Panel'],
    ];

    const row = (key,label,val) => `
      <div class="row" style="gap:10px;align-items:center;margin:10px 0;">
        <div style="min-width:160px;">${label}</div>
        <select data-pol="${key}" class="input"
          style="flex:1;padding:10px;border-radius:12px;border:1px solid rgba(255,255,255,0.14);background:rgba(255,255,255,0.06);color:inherit;">
          <option value="none" ${val==='none'?'selected':''}>User choice</option>
          <option value="on" ${val==='on'?'selected':''}>Forced ON</option>
          <option value="off" ${val==='off'?'selected':''}>Forced OFF</option>
        </select>
        <span class="small muted" style="min-width:110px;text-align:right;">${key}</span>
      </div>
    `;

    const body = `
      <p class="small muted">
        Force modules globally. “User choice” means users can toggle it (unless RBAC hides it).
        Forced OFF hides it everywhere. Forced ON makes it appear for everyone.
      </p>
      <div id="adminModulesBoxInner">
        ${mods.map(([k,l]) => row(k,l, forced[k] || 'none')).join('')}
      </div>
    `;

    const footer = `
      <div class="row" style="gap:10px;align-items:center;">
        <button class="btn primary" id="saveAdminModules" type="button">Save Global Policy</button>
        <span class="small muted" id="adminModulesHint" style="margin-left:auto;"></span>
      </div>
    `;

    return card('Admin: Global Module Policy', body, footer);
  }

  async function loadAdminModulePolicy() {
    const box = byId('adminModulesWrap');
    if (!box) return;

    const j = await fetchJSON('/api/admin_modules_get.php', { credentials: 'same-origin' });
    if (!j.ok) {
      // Not admin or error - hide the section
      box.innerHTML = '';
      return;
    }

    // Convert policies to forced map
    const policies = j.policies || {};
    const mods = [
      ['calendar','Calendar'],
      ['browse','Browse'],
      ['myshows','My Shows'],
      ['settings','Settings'],
      ['mod','Mod Tools'],
      ['admin','Admin Panel'],
      ['trakt','Trakt'],
      ['seedr','Seedr'],
      ['jackett','Jackett'],
      ['prowlarr','Prowlarr'],
    ];

    let rowsHtml = '';
    for (const [key, label] of mods) {
      const pol = policies[key] || {};
      const val = pol.forced || 'none';
      rowsHtml += `
        <div class="row" style="gap:10px;align-items:center;margin:10px 0;">
          <div style="min-width:160px;">${label}</div>
          <select data-pol="${key}" class="input"
            style="flex:1;padding:10px;border-radius:12px;border:1px solid rgba(255,255,255,0.14);background:rgba(255,255,255,0.06);color:inherit;">
            <option value="none" ${val==='none'?'selected':''}>User choice</option>
            <option value="on" ${val==='on'?'selected':''}>Forced ON</option>
            <option value="off" ${val==='off'?'selected':''}>Forced OFF</option>
          </select>
          <span class="small muted" style="min-width:110px;text-align:right;">${key}</span>
        </div>
      `;
    }

    // Render as accordion item
    box.innerHTML = `
      <div class="accordion-item">
        <div class="accordion-header" onclick="this.parentElement.classList.toggle('open')">
          <div>
            <h3>Admin: Module Policy</h3>
            <p class="small muted" style="margin:4px 0 0;">System-wide module control (admin only)</p>
          </div>
          <span class="accordion-icon">▼</span>
        </div>
        <div class="accordion-content">
          <div class="accordion-body">
            <p class="small muted">
              Force modules globally. "User choice" means users can toggle it (unless RBAC hides it).
              Forced OFF hides it everywhere. Forced ON makes it appear for everyone.
            </p>
            <div id="adminModulesBoxInner">
              ${rowsHtml}
            </div>
            <div class="row" style="gap:10px;align-items:center;margin-top:16px;">
              <button class="btn primary" id="saveAdminModules" type="button">Save Global Policy</button>
              <span class="small muted" id="adminModulesHint" style="margin-left:auto;"></span>
            </div>
          </div>
        </div>
      </div>
    `;
  }

  async function saveAdminModules() {
    const hint = byId('adminModulesHint');
    const status = byId('settingsStatus');
    const wrap = byId('adminModulesWrap');
    if (!wrap) return;

    const payload = { forced: {} };
    wrap.querySelectorAll('select[data-pol]').forEach(sel => {
      payload.forced[sel.getAttribute('data-pol')] = sel.value;
    });

    if (status) status.textContent = 'Saving global module policy…';

    const j = await fetchJSON('/api/admin_modules_save.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    if (!j.ok) {
      if (hint) hint.textContent = 'Save failed: ' + (j.error || 'unknown');
      if (status) status.textContent = `Save failed.${j.url ? ' @ ' + j.url : ''}${j.head ? ' | ' + j.head : ''}`;
      return;
    }

    if (hint) hint.textContent = 'Saved.';
    if (status) status.textContent = 'Saved. Reloading…';
    await loadSettings();
  }

  // -------------------- MAIN LOAD --------------------

  async function loadSettings() {
    if (!pageIsSettings()) return;

    const status = byId('settingsStatus');
    const modulesBox = byId('modulesBox');
    const integrationsBox = byId('integrationsBox');
    const hint = byId('saveHint');

    if (!status || !modulesBox || !integrationsBox) return;

    status.textContent = 'Loading…';
    if (hint) hint.textContent = '';

    // 1) Settings & module policy
    const sjson = await fetchJSON('/api/settings_get.php', { credentials: 'same-origin' });
    if (!sjson.ok) {
      status.textContent =
        'Failed: ' + (sjson.error || 'settings_get failed') +
        (sjson.url ? ` @ ${sjson.url}` : '') +
        (sjson.head ? ` | ${sjson.head}` : '');
      return;
    }

    const settings = sjson.settings || {};
    const userMods = settings.modules || {};
    const policy = sjson.modules || {};

    const modList = [
      ['browse', 'Browse'],
      ['myshows', 'My Shows'],
      ['trakt', 'Trakt'],
      ['seedr', 'Seedr (v1/v2)'],
      ['jackett', 'Jackett'],
      ['prowlarr', 'Prowlarr'],
    ];

    let mhtml = '';
    for (const [id, label] of modList) {
      const forced = policy?.forced?.[id];
      const disabled = forced === 'on' || forced === 'off';
      const value = (forced === 'on') ? true : (forced === 'off') ? false : !!userMods[id];
      const note = (forced === 'on') ? 'forced ON' : (forced === 'off') ? 'forced OFF' : '';
      mhtml += checkbox(id, label, value, disabled, note);
    }
    modulesBox.innerHTML = mhtml;

    // 2) Integrations
    const ij = await fetchJSON('/api/integrations_get.php', { credentials: 'same-origin' });
    if (!ij.ok) {
      integrationsBox.innerHTML =
        `<p class="small muted">Failed to load integrations: ${ij.error || 'unknown'}${ij.url ? ' @ ' + ij.url : ''}</p>`;
      status.textContent =
        'Loaded with errors.' +
        (ij.head ? ' | ' + ij.head : '');
      return;
    }

    const integrations = ij.integrations || {};
    const types = ['trakt','seedr','jackett','prowlarr'];

    integrationsBox.innerHTML = types
      .map(t => renderIntegrationCard(t, integrations[t] || { enabled:false, api_variant:'auto', base_url:'', secrets:{} }))
      .join('');

    // 3) Admin global policy
    await loadAdminModulePolicy();

    status.textContent = 'Loaded.';
  }

  // -------------------- EVENTS (HOTSWAP SAFE) --------------------

  document.addEventListener('click', (e) => {
    const t = e.target;
    if (!t) return;

    if (t.matches('[data-int-save]')) {
      e.preventDefault();
      saveIntegration(t.getAttribute('data-int-save'));
      return;
    }
    if (t.matches('[data-int-test]')) {
      e.preventDefault();
      testIntegration(t.getAttribute('data-int-test'));
      return;
    }

    if (t.id === 'saveAdminModules') {
      e.preventDefault();
      saveAdminModules();
      return;
    }

    if (t.id === 'reloadSettings') {
      e.preventDefault();
      loadSettings();
      return;
    }
  });

  document.addEventListener('DOMContentLoaded', () => {
    if (pageIsSettings()) loadSettings();
  });

  window.addEventListener('wnx:page', () => {
    if (pageIsSettings()) loadSettings();
  });
})();
