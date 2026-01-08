<div class="card">
  <div class="hd">
    <h2>Calendar</h2>
    <div class="toolbar">
      <label>
        <input type="checkbox" id="tracked-only-toggle" <?= current_user() ? '' : 'disabled' ?>>
        My Shows Only
      </label>
      <select id="calendar-view-type" style="padding:6px 12px;border-radius:6px;border:1px solid rgba(255,255,255,0.14);background:rgba(255,255,255,0.06);color:inherit;">
        <option value="grid">Calendar Grid</option>
        <option value="list">List View</option>
      </select>
      <button id="prev-month" class="btn">◀ Prev</button>
      <button id="next-month" class="btn">Next ▶</button>
      <button id="refresh-calendar" class="btn">Refresh</button>
    </div>
  </div>
  <div class="bd">
    <div id="calendar-month-label" style="text-align:center;font-size:1.2rem;font-weight:600;margin-bottom:16px;"></div>
    <div id="calendar-container"><p>Loading events...</p></div>
  </div>
</div>

<style>
.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 1px;
  background: rgba(255,255,255,0.1);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 8px;
  overflow: hidden;
}
.calendar-header {
  background: rgba(255,255,255,0.05);
  padding: 12px;
  text-align: center;
  font-weight: 600;
  font-size: 0.9rem;
}
.calendar-day {
  background: rgba(255,255,255,0.02);
  min-height: 100px;
  padding: 8px;
  position: relative;
  cursor: pointer;
  transition: background 0.2s;
}
.calendar-day:hover {
  background: rgba(255,255,255,0.05);
}
.calendar-day.other-month {
  opacity: 0.3;
}
.calendar-day.today {
  background: rgba(0,150,255,0.1);
  border: 2px solid rgba(0,150,255,0.5);
}
.day-number {
  font-weight: 600;
  margin-bottom: 6px;
  font-size: 0.9rem;
}
.day-events {
  font-size: 0.75rem;
}
.day-event {
  background: rgba(100,150,255,0.3);
  padding: 2px 4px;
  margin: 2px 0;
  border-radius: 3px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.day-event-count {
  background: rgba(255,100,100,0.4);
  padding: 2px 6px;
  border-radius: 3px;
  font-size: 0.7rem;
  margin-top: 4px;
  text-align: center;
}
</style>

<script>
(async function() {
  const container = document.getElementById('calendar-container');
  const monthLabel = document.getElementById('calendar-month-label');
  const trackedToggle = document.getElementById('tracked-only-toggle');
  const viewTypeSelect = document.getElementById('calendar-view-type');
  
  let currentMonth = new Date();
  let viewType = 'grid';
  let cachedEvents = [];

  async function loadEvents() {
    try {
      container.innerHTML = '<p>Loading...</p>';
      
      const year = currentMonth.getFullYear();
      const month = currentMonth.getMonth();
      const firstDay = new Date(year, month, 1);
      const lastDay = new Date(year, month + 1, 0);
      
      monthLabel.textContent = firstDay.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
      
      const params = new URLSearchParams({
        start: firstDay.toISOString().split('T')[0],
        end: lastDay.toISOString().split('T')[0],
        tracked_only: trackedToggle?.checked ? '1' : '0'
      });

      const resp = await fetch('/api/events.php?' + params.toString());
      
      if (!resp.ok) {
        container.innerHTML = '<p class="error">Server error: HTTP ' + resp.status + '</p>';
        return;
      }
      
      const data = await resp.json();

      if (!data.ok) {
        container.innerHTML = '<p class="error">Failed: ' + (data.error || 'Unknown error') + '</p>';
        return;
      }

      cachedEvents = data.events || [];
      
      if (cachedEvents.length === 0) {
        container.innerHTML = '<p>No events this month. <a href="?page=mod">Import TV schedule</a> to see shows.</p>';
        return;
      }
      
      if (viewType === 'grid') {
        renderCalendarGrid();
      } else {
        renderListView();
      }
    } catch (err) {
      console.error('Calendar load error:', err);
      container.innerHTML = '<p class="error">Error loading calendar: ' + err.message + '<br>Check browser console (F12) for details.</p>';
    }
  }
  
  function renderCalendarGrid() {
    if (cachedEvents.length === 0) {
      container.innerHTML = '<p>No events this month.</p>';
      return;
    }
    
    const year = currentMonth.getFullYear();
    const month = currentMonth.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startDay = firstDay.getDay(); // 0 = Sunday
    const daysInMonth = lastDay.getDate();
    
    // Group events by date
    const eventsByDate = {};
    cachedEvents.forEach(e => {
      const date = e.start.split(' ')[0];
      eventsByDate[date] = eventsByDate[date] || [];
      eventsByDate[date].push(e);
    });
    
    const today = new Date().toISOString().split('T')[0];
    
    let html = '<div class="calendar-grid">';
    
    // Headers
    ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].forEach(day => {
      html += `<div class="calendar-header">${day}</div>`;
    });
    
    // Previous month padding
    const prevMonthDays = new Date(year, month, 0).getDate();
    for (let i = startDay - 1; i >= 0; i--) {
      const day = prevMonthDays - i;
      html += `<div class="calendar-day other-month"><div class="day-number">${day}</div></div>`;
    }
    
    // Current month days
    for (let day = 1; day <= daysInMonth; day++) {
      const date = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
      const events = eventsByDate[date] || [];
      const isToday = date === today;
      
      html += `<div class="calendar-day ${isToday ? 'today' : ''}" data-date="${date}">`;
      html += `<div class="day-number">${day}</div>`;
      html += '<div class="day-events">';
      
      events.slice(0, 3).forEach(evt => {
        const title = evt.show.title.length > 20 ? evt.show.title.substring(0, 17) + '...' : evt.show.title;
        html += `<div class="day-event" title="${evt.show.title}">${title}</div>`;
      });
      
      if (events.length > 3) {
        html += `<div class="day-event-count">+${events.length - 3} more</div>`;
      }
      
      html += '</div></div>';
    }
    
    // Next month padding
    const totalCells = startDay + daysInMonth;
    const remainingCells = (7 - (totalCells % 7)) % 7;
    for (let i = 1; i <= remainingCells; i++) {
      html += `<div class="calendar-day other-month"><div class="day-number">${i}</div></div>`;
    }
    
    html += '</div>';
    container.innerHTML = html;
  }
  
  async function renderListView() {
    if (cachedEvents.length === 0) {
      container.innerHTML = '<p>No events found.</p>';
      return;
    }
    
    // Get list of tracked show IDs
    let trackedIds = new Set();
    if (<?= current_user() ? 'true' : 'false' ?>) {
      try {
        const trackResp = await fetch('/api/myshows.php');
        const trackData = await trackResp.json();
        if (trackData.ok && trackData.shows) {
          trackedIds = new Set(trackData.shows.map(s => s.id));
        }
      } catch {}
    }
    
    const byDate = {};
    cachedEvents.forEach(e => {
      const d = e.start.split(' ')[0];
      byDate[d] = byDate[d] || [];
      byDate[d].push(e);
    });

    let html = '';
    Object.keys(byDate).sort().forEach(date => {
      html += `<div class="calendar-date"><h3>${date}</h3><div class="events">`;
      byDate[date].forEach(evt => {
        const time = evt.start.split(' ')[1]?.substring(0,5) || '';
        const hasEpisode = evt.season && evt.episode;
        const isTracked = trackedIds.has(evt.show.id);
        
        html += `
          <div class="event" style="display:flex;gap:12px;padding:12px;border:1px solid rgba(255,255,255,0.1);border-radius:8px;margin-bottom:10px;background:rgba(255,255,255,0.02);">
            <img src="${evt.show.poster || '/assets/placeholder.png'}" class="poster" style="width:60px;height:90px;object-fit:cover;border-radius:4px;">
            <div class="info" style="flex:1;">
              <div class="title" style="font-weight:600;margin-bottom:4px;">${evt.show.title}</div>
              <div class="episode" style="margin-bottom:4px;opacity:0.9;">${hasEpisode ? 'S'+String(evt.season).padStart(2,'0')+'E'+String(evt.episode).padStart(2,'0') : evt.type}${evt.title ? ' - '+evt.title : ''}</div>
              <div class="meta" style="font-size:0.85rem;opacity:0.7;">${time} • ${evt.platform || 'Unknown'}</div>
              ${<?= current_user() ? 'true' : 'false' ?> ? `
              <div style="margin-top:8px;">
                ${isTracked ? 
                  `<span class="badge" style="background:rgba(0,255,0,0.2);color:#0f0;font-size:0.75rem;padding:4px 8px;">✓ Tracked</span>` :
                  `<button class="btn small track-show-btn" data-show-id="${evt.show.id}" data-show-title="${evt.show.title}" style="padding:4px 10px;font-size:0.85rem;">+ Track Show</button>`
                }
              </div>
              ` : ''}
            </div>
            ${hasEpisode && <?= current_user() ? 'true' : 'false' ?> ? `
            <div class="actions" style="position:relative;">
              <button class="btn small download-btn" data-show="${evt.show.title}" data-season="${evt.season}" data-episode="${evt.episode}" style="padding:6px 12px;font-size:0.9rem;">
                Download ▾
              </button>
              <div class="download-menu" style="display:none;position:absolute;right:0;top:100%;margin-top:4px;background:var(--bg-secondary);border:1px solid var(--border);border-radius:8px;min-width:180px;z-index:100;box-shadow:0 4px 12px rgba(0,0,0,0.3);">
                <a href="#" class="download-action" data-action="jackett" data-show="${evt.show.title}" data-season="${evt.season}" data-episode="${evt.episode}" style="display:block;padding:10px 14px;text-decoration:none;color:inherit;border-bottom:1px solid rgba(255,255,255,0.1);">
                  🔍 Search Jackett
                </a>
                <a href="#" class="download-action" data-action="prowlarr" data-show="${evt.show.title}" data-season="${evt.season}" data-episode="${evt.episode}" style="display:block;padding:10px 14px;text-decoration:none;color:inherit;border-bottom:1px solid rgba(255,255,255,0.1);">
                  🔍 Search Prowlarr
                </a>
                <a href="#" class="download-action" data-action="seedr" data-show="${evt.show.title}" data-season="${evt.season}" data-episode="${evt.episode}" style="display:block;padding:10px 14px;text-decoration:none;color:inherit;">
                  📥 Send to Seedr
                </a>
              </div>
            </div>
            ` : ''}
          </div>`;
      });
      html += '</div></div>';
    });

    container.innerHTML = html;
  }
  
  // Event handlers
  trackedToggle?.addEventListener('change', loadEvents);
  document.getElementById('refresh-calendar').addEventListener('click', loadEvents);
  document.getElementById('prev-month').addEventListener('click', () => {
    currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() - 1, 1);
    loadEvents();
  });
  document.getElementById('next-month').addEventListener('click', () => {
    currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 1);
    loadEvents();
  });
  viewTypeSelect.addEventListener('change', () => {
    viewType = viewTypeSelect.value;
    if (viewType === 'grid') {
      renderCalendarGrid();
    } else {
      renderListView();
    }
  });
  
  // Click on calendar day to show events for that day
  document.addEventListener('click', (e) => {
    const day = e.target.closest('.calendar-day');
    if (day && !day.classList.contains('other-month')) {
      const date = day.dataset.date;
      const events = cachedEvents.filter(evt => evt.start.startsWith(date));
      if (events.length > 0) {
        showDayEventsModal(date, events);
      }
    }
  });
  
  function showDayEventsModal(date, events) {
    const modal = document.createElement('div');
    modal.id = 'dayEventsModal';
    modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.85);z-index:9999;display:flex;align-items:center;justify-content:center;';
    
    let html = '<div style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:16px;padding:24px;max-width:700px;width:90%;max-height:80vh;overflow-y:auto;">';
    html += `<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">`;
    html += `<h3 style="margin:0;">${new Date(date).toLocaleDateString('en-US', {weekday: 'long', month: 'long', day: 'numeric'})}</h3>`;
    html += `<button onclick="this.closest('#dayEventsModal').remove()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:inherit;padding:0 8px;">×</button>`;
    html += '</div>';
    
    events.forEach(evt => {
      const time = evt.start.split(' ')[1]?.substring(0,5) || '';
      const hasEpisode = evt.season && evt.episode;
      html += `
        <div style="display:flex;gap:12px;padding:12px;border:1px solid rgba(255,255,255,0.1);border-radius:8px;margin-bottom:10px;">
          <img src="${evt.show.poster || '/assets/placeholder.png'}" style="width:50px;height:75px;object-fit:cover;border-radius:4px;">
          <div style="flex:1;">
            <div style="font-weight:600;">${evt.show.title}</div>
            <div style="margin:4px 0;opacity:0.9;">${hasEpisode ? 'S'+String(evt.season).padStart(2,'0')+'E'+String(evt.episode).padStart(2,'0') : evt.type}${evt.title ? ' - '+evt.title : ''}</div>
            <div style="font-size:0.85rem;opacity:0.7;">${time} • ${evt.platform || 'Unknown'}</div>
          </div>
        </div>
      `;
    });
    
    html += '</div>';
    modal.innerHTML = html;
    modal.addEventListener('click', (e) => {
      if (e.target === modal) modal.remove();
    });
    document.body.appendChild(modal);
  }
  
  // Track show button handler
  document.addEventListener('click', async (e) => {
        const time = evt.start.split(' ')[1]?.substring(0,5) || '';
        const hasEpisode = evt.season && evt.episode;
        const isTracked = trackedIds.has(evt.show.id);
        
        html += `
          <div class="event" style="display:flex;gap:12px;padding:12px;border:1px solid rgba(255,255,255,0.1);border-radius:8px;margin-bottom:10px;background:rgba(255,255,255,0.02);">
            <img src="${evt.show.poster || '/assets/placeholder.png'}" class="poster" style="width:60px;height:90px;object-fit:cover;border-radius:4px;">
            <div class="info" style="flex:1;">
              <div class="title" style="font-weight:600;margin-bottom:4px;">${evt.show.title}</div>
              <div class="episode" style="margin-bottom:4px;opacity:0.9;">${hasEpisode ? 'S'+String(evt.season).padStart(2,'0')+'E'+String(evt.episode).padStart(2,'0') : evt.type}${evt.title ? ' - '+evt.title : ''}</div>
              <div class="meta" style="font-size:0.85rem;opacity:0.7;">${time} • ${evt.platform || 'Unknown'}</div>
              ${<?= current_user() ? 'true' : 'false' ?> ? `
              <div style="margin-top:8px;">
                ${isTracked ? 
                  `<span class="badge" style="background:rgba(0,255,0,0.2);color:#0f0;font-size:0.75rem;padding:4px 8px;">✓ Tracked</span>` :
                  `<button class="btn small track-show-btn" data-show-id="${evt.show.id}" data-show-title="${evt.show.title}" style="padding:4px 10px;font-size:0.85rem;">+ Track Show</button>`
                }
              </div>
              ` : ''}
            </div>
            ${hasEpisode && <?= current_user() ? 'true' : 'false' ?> ? `
            <div class="actions" style="position:relative;">
              <button class="btn small download-btn" data-show="${evt.show.title}" data-season="${evt.season}" data-episode="${evt.episode}" style="padding:6px 12px;font-size:0.9rem;">
                Download ▾
              </button>
              <div class="download-menu" style="display:none;position:absolute;right:0;top:100%;margin-top:4px;background:var(--bg-secondary);border:1px solid var(--border);border-radius:8px;min-width:180px;z-index:100;box-shadow:0 4px 12px rgba(0,0,0,0.3);">
                <a href="#" class="download-action" data-action="jackett" data-show="${evt.show.title}" data-season="${evt.season}" data-episode="${evt.episode}" style="display:block;padding:10px 14px;text-decoration:none;color:inherit;border-bottom:1px solid rgba(255,255,255,0.1);">
                  🔍 Search Jackett
                </a>
                <a href="#" class="download-action" data-action="prowlarr" data-show="${evt.show.title}" data-season="${evt.season}" data-episode="${evt.episode}" style="display:block;padding:10px 14px;text-decoration:none;color:inherit;border-bottom:1px solid rgba(255,255,255,0.1);">
                  🔍 Search Prowlarr
                </a>
                <a href="#" class="download-action" data-action="seedr" data-show="${evt.show.title}" data-season="${evt.season}" data-episode="${evt.episode}" style="display:block;padding:10px 14px;text-decoration:none;color:inherit;">
                  📥 Send to Seedr
                </a>
              </div>
            </div>
            ` : ''}
          </div>`;
      });
      html += '</div></div>';
    });

    container.innerHTML = html;
  }

  trackedToggle?.addEventListener('change', loadEvents);
  document.getElementById('refresh-calendar').addEventListener('click', loadEvents);
  
  // Track show button handler
  document.addEventListener('click', async (e) => {
    if (!e.target.classList.contains('track-show-btn')) return;
    e.preventDefault();
    
    const btn = e.target;
    const showId = btn.dataset.showId;
    const showTitle = btn.dataset.showTitle;
    
    btn.disabled = true;
    btn.textContent = 'Tracking...';
    
    try {
      const resp = await fetch('/api/myshows.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'add', show_id: parseInt(showId)})
      });
      
      const data = await resp.json();
      
      if (data.ok) {
        btn.outerHTML = `<span class="badge" style="background:rgba(0,255,0,0.2);color:#0f0;font-size:0.75rem;padding:4px 8px;">✓ Tracked</span>`;
      } else {
        btn.textContent = '+ Track Show';
        btn.disabled = false;
        alert('Failed to track show: ' + (data.error || 'unknown'));
      }
    } catch (err) {
      btn.textContent = '+ Track Show';
      btn.disabled = false;
      alert('Error: ' + err.message);
    }
  });
  
  // Download dropdown toggle
  document.addEventListener('click', (e) => {
    if (e.target.classList.contains('download-btn')) {
      e.preventDefault();
      const menu = e.target.nextElementSibling;
      
      // Close all other menus
      document.querySelectorAll('.download-menu').forEach(m => {
        if (m !== menu) m.style.display = 'none';
      });
      
      // Toggle this menu
      menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
      return;
    }
    
    // Close menus when clicking outside
    if (!e.target.closest('.actions')) {
      document.querySelectorAll('.download-menu').forEach(m => m.style.display = 'none');
    }
  });
  
  // Download action handler
  document.addEventListener('click', async (e) => {
    if (!e.target.classList.contains('download-action')) return;
    e.preventDefault();
    
    const action = e.target.dataset.action;
    const show = e.target.dataset.show;
    const season = e.target.dataset.season;
    const episode = e.target.dataset.episode;
    
    // Close dropdown
    e.target.closest('.download-menu').style.display = 'none';
    
    // Build query
    const query = `${show} S${String(season).padStart(2,'0')}E${String(episode).padStart(2,'0')}`;
    
    // Show loading modal
    showModal('Searching...', `<p>Searching for: <strong>${query}</strong></p><p class="small muted">Please wait...</p>`);
    
    try {
      const resp = await fetch('/api/download_action.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action, query, show, season: parseInt(season), episode: parseInt(episode)})
      });
      
      const data = await resp.json();
      
      if (!data.ok) {
        showModal('Search Failed', `<p class="error">${data.error || 'Unknown error'}</p>`);
        return;
      }
      
      if (action === 'seedr') {
        showModal('Seedr', `<p>${data.message || 'Feature coming soon'}</p>`);
        return;
      }
      
      // Show results
      if (data.results && data.results.length > 0) {
        showResults(query, data.results);
      } else {
        showModal('No Results', `<p>No torrents found for: <strong>${query}</strong></p>`);
      }
      
    } catch (err) {
      showModal('Error', `<p class="error">Request failed: ${err.message}</p>`);
    }
  });
  
  function showModal(title, content) {
    const existing = document.getElementById('downloadModal');
    if (existing) existing.remove();
    
    const modal = document.createElement('div');
    modal.id = 'downloadModal';
    modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.85);z-index:9999;display:flex;align-items:center;justify-content:center;';
    modal.innerHTML = `
      <div style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:16px;padding:24px;max-width:600px;width:90%;max-height:80vh;overflow-y:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
          <h3 style="margin:0;">${title}</h3>
          <button onclick="this.closest('#downloadModal').remove()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:inherit;padding:0 8px;">×</button>
        </div>
        <div>${content}</div>
      </div>
    `;
    
    modal.addEventListener('click', (e) => {
      if (e.target === modal) modal.remove();
    });
    
    document.body.appendChild(modal);
  }
  
  function showResults(query, results) {
    const rows = results.map(r => `
      <tr style="border-bottom:1px solid rgba(255,255,255,0.1);">
        <td style="padding:12px 8px;">
          <div style="font-weight:500;margin-bottom:4px;">${r.title || 'Unknown'}</div>
          <div style="font-size:0.85rem;opacity:0.7;">
            ${formatBytes(r.size)} • Seeds: ${r.seeders || 0}
          </div>
        </td>
        <td style="padding:12px 8px;text-align:right;">
          ${r.magnet ? `<a href="${r.magnet}" class="btn small" style="padding:6px 12px;font-size:0.85rem;margin-right:6px;">Magnet</a>` : ''}
          ${r.download ? `<a href="${r.download}" class="btn small" style="padding:6px 12px;font-size:0.85rem;">Download</a>` : ''}
        </td>
      </tr>
    `).join('');
    
    const content = `
      <p style="margin-bottom:16px;">Found <strong>${results.length}</strong> results for: <strong>${query}</strong></p>
      <table style="width:100%;border-collapse:collapse;">
        <tbody>${rows}</tbody>
      </table>
    `;
    
    showModal('Search Results', content);
  }
  
  function formatBytes(bytes) {
    if (!bytes || bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
  }
  
  loadEvents();
})();
</script>
