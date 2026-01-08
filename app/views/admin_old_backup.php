<?php
declare(strict_types=1);
require_role_html('admin');
?>
<div class="card">
  <div class="hd"><h2>Admin Panel</h2><div class="spacer"></div><span class="small">Full system control</span></div>
  <div class="bd">
    <p class="m0">Manage system-wide settings, data sources, and module policies.</p>

    <div class="mt16 grid2">
      <!-- System Data Sources - FIRST PRIORITY -->
      <div class="card">
        <div class="hd"><h2>System Data Sources</h2></div>
        <div class="bd">
          <p class="small m0" style="margin-bottom:16px;">APIs that populate calendar data for all users.</p>
          
          <!-- TVMaze - Active -->
          <div style="margin-bottom:12px;padding:14px;border:1px solid rgba(0,255,0,0.3);border-radius:10px;background:rgba(0,255,0,0.05);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
              <h3 style="margin:0;font-size:1.05rem;display:flex;align-items:center;gap:8px;">
                <span style="color:#0f0;">✓</span> TVMaze
              </h3>
              <span class="badge" style="background:rgba(0,255,0,0.25);color:#0f0;font-size:0.75rem;padding:4px 10px;">ACTIVE</span>
            </div>
            <p class="small muted" style="margin:0 0 10px;">TV show schedules (US, UK, CA, AU) - Public API</p>
            <p class="small" style="margin:0;"><strong>Import via:</strong> Mod Tools → TVMaze Importer</p>
          </div>
          
          <!-- Trakt - Coming Soon -->
          <div style="margin-bottom:12px;padding:14px;border:1px solid rgba(255,255,255,0.1);border-radius:10px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
              <h3 style="margin:0;font-size:1.05rem;">Trakt</h3>
              <span class="badge" style="background:rgba(255,165,0,0.2);color:#fa0;font-size:0.75rem;padding:4px 10px;">PHASE 9</span>
            </div>
            <p class="small muted" style="margin:0;">User show tracking + recommendations (OAuth)</p>
          </div>
          
          <!-- TheTVDB - Coming Soon -->
          <div style="margin-bottom:0;padding:14px;border:1px solid rgba(255,255,255,0.1);border-radius:10px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
              <h3 style="margin:0;font-size:1.05rem;">TheTVDB</h3>
              <span class="badge" style="background:rgba(255,165,0,0.2);color:#fa0;font-size:0.75rem;padding:4px 10px;">PHASE 9</span>
            </div>
            <p class="small muted" style="margin:0;">Show metadata + artwork enrichment</p>
          </div>
        </div>
      </div>

      <!-- Module Policy -->
      <div class="card">
        <div class="hd"><h2>Module Policy</h2></div>
        <div class="bd">
          <p class="small m0" style="margin-bottom:16px;">Control which features are available system-wide.</p>
          <div id="adminModulesWrap">
            <p class="small muted">Loading module policy...</p>
          </div>
        </div>
      </div>

      <!-- User Management -->
      <div class="card">
        <div class="hd"><h2>User Management</h2></div>
        <div class="bd">
          <p class="small m0">View and manage user accounts & roles.</p>
          <div class="mt16 row wrap">
            <button class="btn" type="button" disabled>View All Users</button>
            <button class="btn" type="button" disabled>Manage Roles</button>
          </div>
          <p class="small muted" style="margin-top:12px;">Coming in Phase 8: User admin UI</p>
        </div>
      </div>

      <!-- System Maintenance -->
      <div class="card">
        <div class="hd"><h2>System Maintenance</h2></div>
        <div class="bd">
          <p class="small m0">Database health & logging tools.</p>
          <div class="mt16 row wrap">
            <button class="btn" type="button" disabled>View Audit Logs</button>
            <button class="btn" type="button" disabled>Database Stats</button>
          </div>
          <p class="small muted" style="margin-top:12px;">Coming in Phase 8: Audit logging UI</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Load admin module policy on page load
(async function() {
  const wrap = document.getElementById('adminModulesWrap');
  if (!wrap) return;
  
  try {
    const resp = await fetch('/api/admin_modules_get.php');
    const data = await resp.json();
    
    if (!data.ok) {
      wrap.innerHTML = `<p class="small error">Failed to load: ${data.error || 'unknown'}</p>`;
      return;
    }
    
    // Convert policies format to forced map
    const policies = data.policies || {};
    const modules = [
      ['calendar', 'Calendar'],
      ['browse', 'Browse'],
      ['myshows', 'My Shows'],
      ['settings', 'Settings'],
      ['mod', 'Mod Tools'],
      ['admin', 'Admin Panel'],
      ['trakt', 'Trakt'],
      ['seedr', 'Seedr'],
      ['jackett', 'Jackett'],
      ['prowlarr', 'Prowlarr'],
    ];
    
    let html = '<div style="display:flex;flex-direction:column;gap:10px;">';
    
    modules.forEach(([id, label]) => {
      const policy = policies[id] || {};
      const forced = policy.forced || 'none';
      
      html += `
        <div style="display:flex;gap:12px;align-items:center;padding:10px;border:1px solid rgba(255,255,255,0.08);border-radius:8px;background:rgba(255,255,255,0.02);">
          <div style="min-width:140px;"><strong>${label}</strong></div>
          <select data-module="${id}" style="flex:1;padding:8px;border-radius:6px;border:1px solid rgba(255,255,255,0.14);background:rgba(255,255,255,0.06);color:inherit;font-size:0.9rem;">
            <option value="none" ${forced === 'none' ? 'selected' : ''}>User Choice</option>
            <option value="on" ${forced === 'on' ? 'selected' : ''}>Force ON</option>
            <option value="off" ${forced === 'off' ? 'selected' : ''}>Force OFF</option>
          </select>
          <span class="small muted" style="min-width:90px;text-align:right;font-size:0.8rem;">${id}</span>
        </div>
      `;
    });
    
    html += '</div>';
    html += '<button id="save-module-policy" class="btn primary" style="margin-top:16px;width:100%;">Save Module Policy</button>';
    html += '<div id="module-policy-status" class="small muted" style="margin-top:8px;text-align:center;"></div>';
    
    wrap.innerHTML = html;
    
    // Save handler
    document.getElementById('save-module-policy').addEventListener('click', async () => {
      const status = document.getElementById('module-policy-status');
      const forced = {};
      
      wrap.querySelectorAll('select[data-module]').forEach(sel => {
        forced[sel.dataset.module] = sel.value;
      });
      
      status.textContent = 'Saving...';
      status.style.color = '';
      
      const saveResp = await fetch('/api/admin_modules_save.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({forced: forced})
      });
      
      const saveData = await saveResp.json();
      
      if (saveData.ok) {
        status.textContent = '✓ Saved successfully!';
        status.style.color = '#0f0';
      } else {
        status.textContent = '✗ Save failed: ' + (saveData.error || 'unknown');
        status.style.color = '#f00';
      }
    });
    
  } catch (err) {
    wrap.innerHTML = `<p class="small error">Error: ${err.message}</p>`;
  }
})();
</script>
