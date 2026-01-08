(() => {
  function qs(sel, root=document){ return root.querySelector(sel); }
  function qsa(sel, root=document){ return Array.from(root.querySelectorAll(sel)); }

  const grid = qs('#calGrid');
  const title = qs('#monthTitle');
  if (!grid || !title) return; // not on calendar page

  const scopeSelect = qs('#scopeSelect');
  const scopeLabel = qs('#scopeLabel');

  const state = {
    month: new Date(),
    scope: (window.WNX && window.WNX.initialScope) ? window.WNX.initialScope : 'all',
    eventsByDay: new Map(),
  };

  // normalize to first of month
  state.month.setDate(1);
  state.month.setHours(0,0,0,0);

  function monthKey(d){
    const y = d.getFullYear();
    const m = String(d.getMonth()+1).padStart(2,'0');
    return `${y}-${m}`;
  }

  function formatMonthTitle(d){
    const fmt = new Intl.DateTimeFormat(undefined, { month:'long', year:'numeric' });
    return fmt.format(d);
  }

  function startOfGrid(d){
    // Monday-first grid
    const first = new Date(d);
    first.setDate(1);
    const jsDow = first.getDay(); // 0=Sun..6=Sat
    const mondayIndex = (jsDow + 6) % 7; // Mon=0..Sun=6
    const start = new Date(first);
    start.setDate(first.getDate() - mondayIndex);
    return start;
  }

  function addDays(d, n){
    const x = new Date(d);
    x.setDate(x.getDate() + n);
    return x;
  }

  function dateKeyLocal(d){
    const y = d.getFullYear();
    const m = String(d.getMonth()+1).padStart(2,'0');
    const day = String(d.getDate()).padStart(2,'0');
    return `${y}-${m}-${day}`;
  }

  function parseUtcToLocal(iso){
    // iso is like 2026-01-03T18:00:00Z
    const dt = new Date(iso);
    return dt;
  }

  function classify(ev){
    if (ev.type === 'drop') return 'drop';
    if (ev.type === 'special') return 'special';
    return 'airing';
  }

  async function loadEvents(){
    const key = monthKey(state.month);
    const url = `api/events.php?month=${encodeURIComponent(key)}&scope=${encodeURIComponent(state.scope)}`;
    state.eventsByDay = new Map();
    try{
      const res = await fetch(url, { cache:'no-store' });
      const data = await res.json();
      if (!data.ok) throw new Error(data.error || 'Failed');
      for (const ev of data.events || []){
        const local = parseUtcToLocal(ev.startUtc);
        const k = dateKeyLocal(local);
        if (!state.eventsByDay.has(k)) state.eventsByDay.set(k, []);
        state.eventsByDay.get(k).push(ev);
      }
      // sort each day by time
      for (const [k, list] of state.eventsByDay){
        list.sort((a,b) => (a.startUtc||'').localeCompare(b.startUtc||''));
      }
    }catch(e){
      console.error(e);
      // leave empty
    }
  }

  function render(){
    title.textContent = formatMonthTitle(state.month);
    if (scopeSelect) scopeSelect.value = state.scope;
    if (scopeLabel) scopeLabel.textContent = state.scope === 'my' ? 'My Shows' : 'All';

    grid.innerHTML = '';

    const start = startOfGrid(state.month);
    const monthIndex = state.month.getMonth();

    for (let i=0; i<42; i++){
      const day = addDays(start, i);
      const inMonth = day.getMonth() === monthIndex;
      const k = dateKeyLocal(day);
      const events = state.eventsByDay.get(k) || [];

      const cell = document.createElement('div');
      cell.className = 'day' + (inMonth ? '' : ' muted');
      cell.dataset.date = k;

      const num = document.createElement('div');
      num.className = 'dnum';
      num.textContent = day.getDate();
      cell.appendChild(num);

      const stack = document.createElement('div');
      stack.className = 'stack';

      const max = 3;
      const shown = events.slice(0, max);
      for (const ev of shown){
        const row = document.createElement('div');
        const kind = classify(ev);
        row.className = 'ev ' + (kind === 'drop' ? 'drop' : kind === 'special' ? 'special' : '');
        const dot = document.createElement('div');
        dot.className = 'dot';
        const txt = document.createElement('div');
        txt.className = 'txt';
        const t = document.createElement('div');
        t.className = 't';
        t.textContent = ev.showTitle;
        const m = document.createElement('div');
        m.className = 'm';
        const se = `S${String(ev.season).padStart(2,'0')}E${String(ev.episode).padStart(2,'0')}`;
        const time = new Date(ev.startUtc).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
        m.textContent = `${time} • ${se} • ${ev.episodeTitle}`;
        txt.appendChild(t); txt.appendChild(m);
        row.appendChild(dot); row.appendChild(txt);
        stack.appendChild(row);
      }

      cell.appendChild(stack);

      if (events.length > max){
        const more = document.createElement('div');
        more.className = 'more';
        more.textContent = `+${events.length - max} more`;
        cell.appendChild(more);
      }

      cell.addEventListener('click', () => openDayModal(k, day));
      grid.appendChild(cell);
    }
  }

  function openDayModal(key, dateObj){
    const modal = qs('#dayModal');
    const list = qs('#dayList');
    const t = qs('#dayTitle');
    if (!modal || !list || !t) return;

    const fmt = new Intl.DateTimeFormat(undefined, { weekday:'long', year:'numeric', month:'long', day:'numeric' });
    t.textContent = fmt.format(dateObj);

    const events = state.eventsByDay.get(key) || [];
    list.innerHTML = '';
    if (!events.length){
      list.innerHTML = `<div class="banner"><div class="badge">Empty</div><div><p>No events for this day.</p></div></div>`;
    } else {
      for (const ev of events){
        const row = document.createElement('div');
        row.className = 'item';

        const when = document.createElement('div');
        when.className = 'when';
        when.textContent = new Date(ev.startUtc).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});

        const main = document.createElement('div');
        main.className = 'main';
        const title = document.createElement('div');
        title.className = 'title';
        title.textContent = ev.showTitle;

        const sub = document.createElement('div');
        sub.className = 'sub';
        const se = `S${String(ev.season).padStart(2,'0')}E${String(ev.episode).padStart(2,'0')}`;
        sub.textContent = `${se} • ${ev.episodeTitle}`;

        main.appendChild(title); main.appendChild(sub);

        const tag = document.createElement('div');
        const kind = classify(ev);
        tag.className = `tag ${kind}`;
        tag.textContent = kind === 'airing' ? 'Airing' : kind === 'drop' ? 'Drop' : 'Special';

        row.appendChild(when);
        row.appendChild(main);
        row.appendChild(tag);

        list.appendChild(row);
      }
    }

    modal.classList.add('open');
  }

  function wireModalClose(){
    const modal = qs('#dayModal');
    const closeBtn = qs('#dayClose');
    if (!modal) return;

    function close(){ modal.classList.remove('open'); }
    closeBtn?.addEventListener('click', close);
    modal.addEventListener('click', (e) => { if (e.target === modal) close(); });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        // close only if open
        if (modal.classList.contains('open')) close();
      }
    });
  }

  async function refresh(){
    await loadEvents();
    render();
  }

  function wireNav(){
    qs('#prevMonth')?.addEventListener('click', async () => {
      state.month.setMonth(state.month.getMonth()-1);
      await refresh();
    });
    qs('#nextMonth')?.addEventListener('click', async () => {
      state.month.setMonth(state.month.getMonth()+1);
      await refresh();
    });
    qs('#todayBtn')?.addEventListener('click', async () => {
      const now = new Date();
      now.setDate(1); now.setHours(0,0,0,0);
      state.month = now;
      await refresh();
    });

    scopeSelect?.addEventListener('change', async () => {
      state.scope = scopeSelect.value;
      await refresh();
    });
  }

  wireModalClose();
  wireNav();
  refresh();
})();
