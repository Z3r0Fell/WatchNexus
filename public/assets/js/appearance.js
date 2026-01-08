/* WatchNexus v3 - appearance.js
 * Unified Appearance Engine:
 * - Theme palettes (paper_ink, grid_noir, ...)
 * - Color scheme (system/light/dark)
 * - UI modes (command/overview/signal/nebula)
 * - Optional scanlines overlay
 *
 * Storage:
 *   wnx.appearance.v3 = { theme, scheme, uiMode, fx }
 * Back-compat migration:
 *   wnx.theme.v3 + wnx_ui_mode
 */

(() => {
  const byId = (id) => document.getElementById(id);

  const KEY = 'wnx.appearance.v3';
  const OLD_THEME_KEY = 'wnx.theme.v3';
  const OLD_UI_MODE_KEY = 'wnx_ui_mode';

  const DEFAULT_THEME = (window.WNX && window.WNX.publicThemeId) ? window.WNX.publicThemeId : 'paper_ink';
  const DEFAULTS = { theme: DEFAULT_THEME, scheme: 'system', uiMode: 'command', fx: false };

  const THEMES = [
    { id: 'paper_ink', name: 'Paper & Ink (Default)' },
    { id: 'grid_noir', name: 'Grid Noir' },
    { id: 'lcars', name: 'LCARS-ish' },
    { id: 'tron_legacy', name: 'TRON-ish (Legacy)' },
    { id: 'tardis_blue', name: 'Doctor-ish (TARDIS Blue)' }
  ];

  const UI_MODES = [
    { id: 'command', label: '⚡ Command', bodyClass: 'mode-command' },
    { id: 'overview', label: '📊 Overview', bodyClass: 'mode-overview' },
    { id: 'signal',  label: '📡 Signal',  bodyClass: 'mode-signal' },
    { id: 'nebula',  label: '🌌 Nebula',  bodyClass: 'mode-nebula' }
  ];

  function safeParse(raw) {
    try { return JSON.parse(raw); } catch { return null; }
  }

  function loadPref() {
    const raw = localStorage.getItem(KEY);
    const obj = raw ? safeParse(raw) : null;
    if (obj && typeof obj === 'object') {
      return {
        theme: obj.theme || DEFAULTS.theme,
        scheme: obj.scheme || obj.mode || DEFAULTS.scheme, // tolerate older field name
        uiMode: obj.uiMode || DEFAULTS.uiMode,
        fx: !!obj.fx
      };
    }

    // Back-compat migration
    const oldThemeRaw = localStorage.getItem(OLD_THEME_KEY);
    const oldThemeObj = oldThemeRaw ? safeParse(oldThemeRaw) : null;
    const oldUiMode = localStorage.getItem(OLD_UI_MODE_KEY);

    const migrated = {
      theme: (oldThemeObj && oldThemeObj.theme) ? oldThemeObj.theme : DEFAULTS.theme,
      scheme: (oldThemeObj && oldThemeObj.mode) ? oldThemeObj.mode : DEFAULTS.scheme,
      uiMode: oldUiMode || DEFAULTS.uiMode,
      fx: !!(oldThemeObj && oldThemeObj.fx)
    };

    try { localStorage.setItem(KEY, JSON.stringify(migrated)); } catch {}
    return migrated;
  }

  function savePref(pref) {
    try { localStorage.setItem(KEY, JSON.stringify(pref)); } catch {}
  }

  function applyScheme(scheme) {
    document.documentElement.setAttribute('data-mode', scheme || 'system');
  }

  function applyTheme(themeId) {
    document.documentElement.setAttribute('data-theme', themeId || DEFAULTS.theme);
  }

  function applyFxScanlines(on) {
    if (on) document.documentElement.setAttribute('data-fx-scanlines', '');
    else document.documentElement.removeAttribute('data-fx-scanlines');

    // Also toggle the legacy scanline class used by base.css
    if (on) document.body.classList.add('fx-scanlines');
    else document.body.classList.remove('fx-scanlines');
  }

  function applyUIMode(uiMode) {
    const m = UI_MODES.find(x => x.id === uiMode) || UI_MODES[0];

    // Remove any existing mode-* classes
    Array.from(document.body.classList).forEach(c => {
      if (c.startsWith('mode-')) document.body.classList.remove(c);
    });

    document.body.classList.add(m.bodyClass);
    document.documentElement.setAttribute('data-ui', m.id);
  }

  function applyAll(pref) {
    applyTheme(pref.theme);
    applyScheme(pref.scheme);
    applyUIMode(pref.uiMode);
    applyFxScanlines(!!pref.fx);
  }

  function populate(pref) {
    const themeSelect = byId('themeSelect');
    if (themeSelect) {
      themeSelect.innerHTML = THEMES.map(t => `<option value="${t.id}">${t.name}</option>`).join('');
      themeSelect.value = pref.theme || DEFAULTS.theme;
    }

    const modeSelect = byId('modeSelect');
    if (modeSelect) modeSelect.value = pref.scheme || 'system';

    const uiModeSelect = byId('uiModeSelect');
    if (uiModeSelect) uiModeSelect.value = pref.uiMode || DEFAULTS.uiMode;

    const fx = byId('fxScanlines');
    if (fx) fx.checked = !!pref.fx;
  }

  function readForm() {
    return {
      theme: byId('themeSelect')?.value || DEFAULTS.theme,
      scheme: byId('modeSelect')?.value || 'system',
      uiMode: byId('uiModeSelect')?.value || DEFAULTS.uiMode,
      fx: !!byId('fxScanlines')?.checked
    };
  }

  function getDocState() {
    const theme = document.documentElement.getAttribute('data-theme') || DEFAULTS.theme;
    const scheme = document.documentElement.getAttribute('data-mode') || 'system';
    const uiMode = document.documentElement.getAttribute('data-ui') || DEFAULTS.uiMode;
    const fx = document.documentElement.hasAttribute('data-fx-scanlines') || document.body.classList.contains('fx-scanlines');
    return { theme, scheme, uiMode, fx };
  }

  function openModal() {
    const m = byId('themeModal');
    if (!m) return;
    m.classList.add('open');
    m.removeAttribute('aria-hidden');
  }

  function closeModal() {
    const m = byId('themeModal');
    if (!m) return;
    m.classList.remove('open');
    m.setAttribute('aria-hidden', 'true');
  }

  function wire() {
    const btn = byId('themeBtn');
    const modal = byId('themeModal');
    const closeBtn = byId('themeClose');

    // Apply saved pref immediately on load (even if theme UI is hidden)
    const saved = loadPref();
    applyAll(saved);

    // If the layout doesn't show the modal/btn on this page, stop after applying.
    if (!btn || !modal) return;

    let snapshot = null;

    // Populate selects now (so hot-swap pages keep it ready)
    populate(saved);

    const livePreview = () => {
      const pref = readForm();
      applyAll(pref);
    };

    btn.addEventListener('click', () => {
      snapshot = getDocState();
      const cur = loadPref();
      populate(cur);
      openModal();
      livePreview();
    });

    closeBtn?.addEventListener('click', () => {
      // Revert to snapshot when closing without applying
      if (snapshot) applyAll(snapshot);
      closeModal();
    });

    // Click backdrop closes
    modal.addEventListener('click', (e) => {
      if (e.target === modal) {
        if (snapshot) applyAll(snapshot);
        closeModal();
      }
    });

    // ESC closes
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && modal.classList.contains('open')) {
        if (snapshot) applyAll(snapshot);
        closeModal();
      }
    });

    // Live preview hooks
    byId('themeSelect')?.addEventListener('change', livePreview);
    byId('modeSelect')?.addEventListener('change', livePreview);
    byId('uiModeSelect')?.addEventListener('change', livePreview);
    byId('fxScanlines')?.addEventListener('change', livePreview);

    // Apply = save + keep changes
    byId('applyTheme')?.addEventListener('click', () => {
      const next = readForm();
      savePref(next);
      applyAll(next);
      snapshot = null;
      closeModal();
    });

    // Reset = defaults
    byId('resetTheme')?.addEventListener('click', () => {
      savePref(DEFAULTS);
      populate(DEFAULTS);
      applyAll(DEFAULTS);
    });

    // Re-apply after hot-swap navigation (app.js swaps #appContent only)
    window.addEventListener('wnx:page', () => {
      const cur = loadPref();
      applyAll(cur);
    });
  }

  document.addEventListener('DOMContentLoaded', wire);
})();
