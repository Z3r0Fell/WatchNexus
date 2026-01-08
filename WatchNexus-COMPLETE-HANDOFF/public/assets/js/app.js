/* WatchNexus v3 - app.js (FULL FILE)
 * - Hot-swap navigation (partial page loads)
 * - Shell state sync from /api/state.php
 * - Role/module-aware tabs
 * - Logout via AJAX
 */

(() => {
  const byId = (id) => document.getElementById(id);

  function setHidden(el, hidden) {
    if (!el) return;
    el.hidden = !!hidden;
  }

  function safeText(v) {
    return (v === null || v === undefined) ? '' : String(v);
  }

  function getCurrentPageFromDOM() {
    const app = byId('appContent');
    return app?.getAttribute('data-page') || 'calendar';
  }

  function parsePageFromURL(url) {
    try {
      const u = new URL(url, window.location.origin);
      return u.searchParams.get('page') || 'calendar';
    } catch {
      return 'calendar';
    }
  }

  function withPartial(url) {
    const u = new URL(url, window.location.origin);
    u.searchParams.set('partial', '1');
    return u.toString();
  }

  function isSameOrigin(url) {
    try {
      const u = new URL(url, window.location.origin);
      return u.origin === window.location.origin;
    } catch {
      return false;
    }
  }

  function isHotSwapLink(a) {
    if (!a) return false;
    if (a.hasAttribute('download')) return false;
    const target = a.getAttribute('target');
    if (target && target !== '' && target !== '_self') return false;
    const href = a.getAttribute('href') || '';
    if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) return false;
    if (!isSameOrigin(href)) return false;
    return a.hasAttribute('data-nav') || href.includes('?page=');
  }

  function dispatchPageEvent(page) {
    try {
      window.dispatchEvent(new CustomEvent('wnx:page', { detail: { page } }));
    } catch {}
  }

  async function fetchState() {
    const res = await fetch('/api/state.php', { credentials: 'same-origin' });
    return await res.json();
  }

  function escapeHtml(str) {
    return safeText(str)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function buildTabs(state) {
    const nav = byId('navTabs');
    if (!nav) return;

    const page = getCurrentPageFromDOM();
    const mods = state?.modules || {};
    const roles = state?.roles || [];
    const loggedIn = !!state?.logged_in;

    const isAdmin = roles.includes('admin');
    const isMod = isAdmin || roles.includes('mod');

    const items = [];
    if (mods.calendar !== false) items.push(['calendar', 'Calendar']);
    if (mods.browse) items.push(['browse', 'Browse']);
    if (loggedIn && mods.myshows) items.push(['myshows', 'My Shows']);
    if (loggedIn && mods.settings) items.push(['settings', 'Settings']);
    if (isMod && mods.mod) items.push(['mod', 'Mod Tools']);
    if (isAdmin && mods.admin) items.push(['admin', 'Admin']);

    nav.innerHTML = items.map(([id, label]) => {
      const active = (page === id) ? 'active' : '';
      return `<a class="tab ${active}" href="/?page=${id}" data-nav>${label}</a>`;
    }).join('');
  }

  function syncHeader(state) {
    const loggedIn = !!state?.logged_in;
    const modules = state?.modules || {};

    setHidden(byId('loginBtn'), loggedIn);
    setHidden(byId('registerBtn'), loggedIn);
    setHidden(byId('logoutBtn'), !loggedIn);

    const themeAllowed = loggedIn && (modules.themes !== false);
    setHidden(byId('themeBtn'), !themeAllowed);

    setHidden(byId('publicBanner'), loggedIn);

    const userPill = byId('userPill');
    if (userPill) {
      userPill.hidden = !loggedIn;
      if (loggedIn && state.user) {
        const label = state.user.display_name || state.user.email || 'User';
        userPill.innerHTML = `Signed in as <strong>${escapeHtml(label)}</strong>`;
      }
    }
  }

  async function refreshShell() {
    try {
      const state = await fetchState();
      if (!state || state.ok !== true) return;
      window.WNX_STATE = state;
      buildTabs(state);
      syncHeader(state);
    } catch (e) {
      console.warn('[WNX] state refresh failed', e);
    }
  }

  async function navigate(url, { push = true } = {}) {
    if (!isSameOrigin(url)) {
      window.location.href = url;
      return;
    }

    const app = byId('appContent');
    if (!app) {
      window.location.href = url;
      return;
    }

    const page = parsePageFromURL(url);
    app.setAttribute('aria-busy', 'true');

    try {
      const res = await fetch(withPartial(url), {
        credentials: 'same-origin',
        headers: { 'X-WNX-AJAX': '1', 'Accept': 'text/html' }
      });

      const html = await res.text();
      app.innerHTML = html;
      app.setAttribute('data-page', page);

      if (push) window.history.pushState({ url }, '', url);

      await refreshShell();
      dispatchPageEvent(page);

      const titleHdr = res.headers.get('X-WNX-Title');
      if (titleHdr) document.title = titleHdr;

    } catch (e) {
      console.warn('[WNX] navigate failed', e);
      window.location.href = url;
    } finally {
      app.removeAttribute('aria-busy');
    }
  }

  function wireLinkInterception() {
    document.addEventListener('click', (ev) => {
      const a = ev.target?.closest ? ev.target.closest('a') : null;
      if (!a) return;
      if (!isHotSwapLink(a)) return;
      if (ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey) return;
      ev.preventDefault();
      const href = a.getAttribute('href');
      if (!href) return;
      navigate(href, { push: true });
    });
  }

  function wirePopState() {
    window.addEventListener('popstate', (ev) => {
      const url = (ev.state && ev.state.url) ? ev.state.url : window.location.href;
      navigate(url, { push: false });
    });
  }

  function wireLogout() {
    const logoutBtn = byId('logoutBtn');
    if (!logoutBtn) return;

    logoutBtn.addEventListener('click', async () => {
      try {
        await fetch('/api/auth.php?ajax=1', {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-WNX-AJAX': '1',
            'Accept': 'application/json'
          },
          body: 'action=logout&ajax=1'
        });
      } catch (e) {
        console.warn('[WNX] logout failed', e);
      }

      await refreshShell();
      navigate('/?page=calendar', { push: true });
    });
  }

  async function boot() {
    wireLinkInterception();
    wirePopState();
    wireLogout();
    await refreshShell();
    dispatchPageEvent(getCurrentPageFromDOM());
  }

  document.addEventListener('DOMContentLoaded', boot);

  // Expose for other scripts
  window.WNX_NAVIGATE = navigate;
  window.WNX_REFRESH_SHELL = refreshShell;
})();
