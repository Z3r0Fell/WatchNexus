/* WatchNexus v3 - theme.js (FULL FILE)
 * - Theme modal open/close
 * - Live preview on changes
 * - Apply saves
 * - Close without Apply reverts
 */

(() => {
  const byId = (id) => document.getElementById(id);

  const KEY = 'wnx.theme.v3';
  const DEFAULT_THEME = (window.WNX && window.WNX.publicThemeId) ? window.WNX.publicThemeId : 'paper_ink';

  // Theme list (we expand later into your mega-engine)
  const THEMES = [
    { id: 'paper_ink', name: 'Paper & Ink (Default)' },
    { id: 'grid_noir', name: 'Grid Noir' },
    { id: 'lcars', name: 'LCARS-ish' },
    { id: 'tron_legacy', name: 'TRON-ish (Legacy)' },
    { id: 'tardis_blue', name: 'Doctor-ish (TARDIS Blue)' }
  ];

  let appliedSnapshot = null; // document state before opening modal
  let didApply = false;

  function loadPref() {
    try {
      const raw = localStorage.getItem(KEY);
      if (!raw) return { theme: DEFAULT_THEME, mode: 'system', fx: false };
      const obj = JSON.parse(raw);
      return {
        theme: obj.theme || DEFAULT_THEME,
        mode: obj.mode || 'system',
        fx: !!obj.fx
      };
    } catch {
      return { theme: DEFAULT_THEME, mode: 'system', fx: false };
    }
  }

  function savePref(pref) {
    try { localStorage.setItem(KEY, JSON.stringify(pref)); } catch {}
  }

  function applyMode(mode) {
    // data-mode: system | light | dark
    const m = mode || 'system';
    document.documentElement.setAttribute('data-mode', m);
  }

  function applyThemeId(themeId, fx) {
    const t = themeId || DEFAULT_THEME;
    document.documentElement.setAttribute('data-theme', t);

    if (fx) document.documentElement.setAttribute('data-fx-scanlines', '');
    else document.documentElement.removeAttribute('data-fx-scanlines');
  }

  function getDocState() {
    const theme = document.documentElement.getAttribute('data-theme') || DEFAULT_THEME;
    const mode = document.documentElement.getAttribute('data-mode') || 'system';
    const fx = document.documentElement.hasAttribute('data-fx-scanlines');
    return { theme, mode, fx };
  }

  function openModal() {
    const m = byId('themeModal');
    if (!m) return;
    m.style.display = 'flex';
    m.classList.add('open');
    m.removeAttribute('aria-hidden');
  }

  function closeModal(revertIfNotApplied = true) {
    const m = byId('themeModal');
    if (!m) return;

    if (revertIfNotApplied && !didApply && appliedSnapshot) {
      applyThemeId(appliedSnapshot.theme, appliedSnapshot.fx);
      applyMode(appliedSnapshot.mode);
    }

    m.style.display = 'none';
    m.classList.remove('open');
    m.setAttribute('aria-hidden', 'true');
  }

  function populate(pref) {
    const themeSelect = byId('themeSelect');
    if (themeSelect) {
      themeSelect.innerHTML = THEMES.map(t => `<option value="${t.id}">${t.name}</option>`).join('');
      themeSelect.value = pref.theme || DEFAULT_THEME;
    }

    const modeSelect = byId('modeSelect');
    if (modeSelect) modeSelect.value = pref.mode || 'system';

    const fx = byId('fxScanlines');
    if (fx) fx.checked = !!pref.fx;
  }

  function readForm() {
    return {
      theme: byId('themeSelect')?.value || DEFAULT_THEME,
      mode: byId('modeSelect')?.value || 'system',
      fx: !!byId('fxScanlines')?.checked
    };
  }

  function livePreview() {
    const pref = readForm();
    applyThemeId(pref.theme, pref.fx);
    applyMode(pref.mode);
  }

  function wire() {
    const btn = byId('themeBtn');
    const modal = byId('themeModal');
    const closeBtn = byId('themeClose');

    // If the layout doesn't show theme UI on this page, exit quietly
    if (!btn || !modal) return;

    // Apply saved pref on load so the site is consistent
    const pref = loadPref();
    applyThemeId(pref.theme, pref.fx);
    applyMode(pref.mode);
    populate(pref);

    // Open modal
    btn.addEventListener('click', () => {
      didApply = false;
      appliedSnapshot = getDocState();

      const p = loadPref();
      populate(p);

      openModal();
      livePreview();
    });

    // Close button
    if (closeBtn) closeBtn.addEventListener('click', () => closeModal(true));

    // Click backdrop to close
    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeModal(true);
    });

    // ESC closes
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeModal(true);
    });

    // ✅ Live preview hooks (THE IMPORTANT BIT)
    byId('themeSelect')?.addEventListener('change', livePreview);
    byId('modeSelect')?.addEventListener('change', livePreview);
    byId('fxScanlines')?.addEventListener('change', livePreview);

    // Apply = save + keep changes
    const applyBtn = byId('applyTheme');
    if (applyBtn) {
      applyBtn.addEventListener('click', () => {
        const next = readForm();
        savePref(next);
        applyThemeId(next.theme, next.fx);
        applyMode(next.mode);
        didApply = true;
        closeModal(false);
      });
    }

    // Reset = revert to defaults
    const resetBtn = byId('resetTheme');
    if (resetBtn) {
      resetBtn.addEventListener('click', () => {
        const next = { theme: DEFAULT_THEME, mode: 'system', fx: false };
        savePref(next);
        populate(next);
        applyThemeId(next.theme, next.fx);
        applyMode(next.mode);
      });
    }
  }

  document.addEventListener('DOMContentLoaded', wire);
})();
