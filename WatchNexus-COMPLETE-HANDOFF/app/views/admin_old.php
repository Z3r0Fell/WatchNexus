<?php
declare(strict_types=1);
require_role_html('admin');
?>
<div class="card">
  <div class="hd"><h2>Admin</h2><div class="spacer"></div><span class="small">All access</span></div>
  <div class="bd">
    <p class="m0">Admin gets everything: users, logs, maintenance, and system config.</p>

    <div class="mt16 grid2">
      <div class="card">
        <div class="hd"><h2>User Management</h2></div>
        <div class="bd">
          <p class="small m0">This scaffold doesn’t persist users yet. In production, roles live in DB and admin can promote/demote.</p>
          <div class="mt16 row wrap">
            <button class="btn" type="button" disabled>Promote User (stub)</button>
            <button class="btn" type="button" disabled>Disable User (stub)</button>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="hd"><h2>Maintenance</h2></div>
        <div class="bd">
          <p class="small m0">Reserved for database repair, log review, and “fix catalog” tools.</p>
          <div class="mt16 row wrap">
            <button class="btn" type="button" disabled>Fix Show Database (stub)</button>
            <button class="btn" type="button" disabled>View Logs (stub)</button>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="hd"><h2>Module Policy</h2><div class="spacer"></div><span class="small">Global control</span></div>
        <div class="bd">
          <p class="small m0">Control which modules are available system-wide. Force modules ON (everyone has it), force OFF (nobody has it), or let users choose.</p>
          <div class="mt16" id="adminModulesWrap">
            <p class="small muted">Loading module policy...</p>
          </div>
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
    
    let html = '<div style="display:flex;flex-direction:column;gap:12px;">';
    
    modules.forEach(([id, label]) => {
      const policy = policies[id] || {};
      const forced = policy.forced || 'none';
      
      html += `
        <div style="display:flex;gap:12px;align-items:center;padding:8px;border:1px solid rgba(255,255,255,0.1);border-radius:8px;">
          <div style="min-width:140px;"><strong>${label}</strong></div>
          <select data-module="${id}" style="flex:1;padding:8px;border-radius:8px;border:1px solid rgba(255,255,255,0.14);background:rgba(255,255,255,0.06);color:inherit;">
            <option value="none" ${forced === 'none' ? 'selected' : ''}>User Choice</option>
            <option value="on" ${forced === 'on' ? 'selected' : ''}>Force ON</option>
            <option value="off" ${forced === 'off' ? 'selected' : ''}>Force OFF</option>
          </select>
          <span class="small muted" style="min-width:80px;text-align:right;">${id}</span>
        </div>
      `;
    });
    
    html += '</div>';
    html += '<button id="save-module-policy" class="btn primary" style="margin-top:16px;">Save Policy</button>';
    html += '<div id="module-policy-status" class="small muted" style="margin-top:8px;"></div>';
    
    wrap.innerHTML = html;
    
    // Save handler
    document.getElementById('save-module-policy').addEventListener('click', async () => {
      const status = document.getElementById('module-policy-status');
      const forced = {};
      
      wrap.querySelectorAll('select[data-module]').forEach(sel => {
        forced[sel.dataset.module] = sel.value;
      });
      
      status.textContent = 'Saving...';
      
      const saveResp = await fetch('/api/admin_modules_save.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({forced: forced})
      });
      
      const saveData = await saveResp.json();
      
      if (saveData.ok) {
        status.textContent = 'Saved successfully!';
        status.style.color = '#0f0';
      } else {
        status.textContent = 'Save failed: ' + (saveData.error || 'unknown');
        status.style.color = '#f00';
      }
    });
    
  } catch (err) {
    wrap.innerHTML = `<p class="small error">Error: ${err.message}</p>`;
  }
})();
</script>
