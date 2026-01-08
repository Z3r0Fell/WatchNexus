<?php
declare(strict_types=1);
require_role_html('admin');

$pdo = db();
?>

<style>
.stat-card {
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 10px;
  padding: 16px;
  margin-bottom: 12px;
}
.stat-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid rgba(255,255,255,0.05);
}
.stat-row:last-child {
  border-bottom: none;
}
.stat-label {
  font-weight: 500;
  opacity: 0.8;
}
.stat-value {
  font-weight: 600;
  font-size: 1.1rem;
}
.status-badge {
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 600;
}
.status-ok { background: rgba(0,255,0,0.2); color: #0f0; }
.status-warn { background: rgba(255,165,0,0.2); color: #fa0; }
.status-error { background: rgba(255,0,0,0.2); color: #f00; }
</style>

<div class="card">
  <div class="hd"><h2>Admin Dashboard</h2><div class="spacer"></div><span class="small">System monitoring & control</span></div>
  <div class="bd">
    
    <div class="mt16 grid2">
      
      <!-- System Health -->
      <div class="card">
        <div class="hd"><h3>System Health</h3></div>
        <div class="bd">
          <div class="stat-card">
            <div class="stat-row">
              <span class="stat-label">PHP Version</span>
              <span class="stat-value"><?= PHP_VERSION ?></span>
            </div>
            <div class="stat-row">
              <span class="stat-label">Database</span>
              <span class="stat-value status-badge status-ok">Connected</span>
            </div>
            <div class="stat-row">
              <span class="stat-label">Disk Space</span>
              <span class="stat-value" id="disk-space">Checking...</span>
            </div>
            <div class="stat-row">
              <span class="stat-label">Memory Usage</span>
              <span class="stat-value"><?= round(memory_get_usage(true) / 1024 / 1024, 2) ?> MB</span>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Database Statistics -->
      <div class="card">
        <div class="hd"><h3>Database Statistics</h3></div>
        <div class="bd">
          <div class="stat-card">
            <?php
            $stats = [
              'Shows' => $pdo->query("SELECT COUNT(*) FROM shows")->fetchColumn(),
              'Events' => $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn(),
              'Users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
              'Tracked Shows' => $pdo->query("SELECT COUNT(*) FROM user_tracked_shows")->fetchColumn(),
              'Integrations' => $pdo->query("SELECT COUNT(*) FROM user_integrations WHERE enabled = 1")->fetchColumn(),
            ];
            foreach ($stats as $label => $value):
            ?>
            <div class="stat-row">
              <span class="stat-label"><?= $label ?></span>
              <span class="stat-value"><?= number_format($value) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
          <button class="btn" onclick="checkIntegrity()" style="width:100%;margin-top:12px;">Check Database Integrity</button>
          <div id="integrity-results" style="margin-top:12px;"></div>
        </div>
      </div>
      
      <!-- Data Sources Configuration -->
      <div class="card">
        <div class="hd"><h3>Data Sources</h3></div>
        <div class="bd">
          
          <!-- TVMaze -->
          <div style="margin-bottom:16px;padding:14px;border:1px solid rgba(0,255,0,0.3);border-radius:10px;background:rgba(0,255,0,0.05);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
              <h4 style="margin:0;font-size:1rem;">✓ TVMaze API</h4>
              <span class="status-badge status-ok" id="tvmaze-status">ACTIVE</span>
            </div>
            <p class="small muted" style="margin:0 0 10px;">Public API - No configuration needed</p>
            <button class="btn small" onclick="testTVMaze()">Test Connection</button>
            <div id="tvmaze-test" style="margin-top:8px;"></div>
          </div>
          
          <!-- Trakt System Config -->
          <div style="margin-bottom:16px;padding:14px;border:1px solid rgba(255,255,255,0.1);border-radius:10px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
              <h4 style="margin:0;font-size:1rem;">Trakt (System-Wide)</h4>
              <span class="status-badge status-warn" id="trakt-status">NOT CONFIGURED</span>
            </div>
            <p class="small muted" style="margin:0 0 10px;">OAuth app for user tracking & sync</p>
            
            <div style="margin-bottom:10px;">
              <label class="small muted" style="display:block;margin-bottom:4px;">Client ID</label>
              <input type="text" id="trakt-client-id" placeholder="Trakt Client ID" style="width:100%;padding:8px;border-radius:6px;border:1px solid rgba(255,255,255,0.14);background:rgba(255,255,255,0.06);color:inherit;">
            </div>
            
            <div style="margin-bottom:10px;">
              <label class="small muted" style="display:block;margin-bottom:4px;">Client Secret</label>
              <input type="password" id="trakt-client-secret" placeholder="Trakt Client Secret" style="width:100%;padding:8px;border-radius:6px;border:1px solid rgba(255,255,255,0.14);background:rgba(255,255,255,0.06);color:inherit;">
            </div>
            
            <button class="btn primary small" onclick="saveTraktConfig()">Save Configuration</button>
            <div id="trakt-save-status" style="margin-top:8px;"></div>
          </div>
          
          <!-- TheTVDB -->
          <div style="padding:14px;border:1px solid rgba(255,255,255,0.1);border-radius:10px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
              <h4 style="margin:0;font-size:1rem;">TheTVDB</h4>
              <span class="status-badge status-warn">PHASE 9</span>
            </div>
            <p class="small muted" style="margin:0;">Enhanced metadata & artwork (coming soon)</p>
          </div>
        </div>
      </div>
      
      <!-- Import Progress / Activity Monitor -->
      <div class="card">
        <div class="hd"><h3>Import Activity</h3></div>
        <div class="bd">
          <div id="import-progress">
            <p class="small muted">No imports currently running</p>
          </div>
          <button class="btn small" onclick="refreshImportStatus()" style="margin-top:12px;">Refresh Status</button>
        </div>
      </div>
      
      <!-- User Activity -->
      <div class="card">
        <div class="hd"><h3>User Activity (Last 7 Days)</h3></div>
        <div class="bd">
          <div class="stat-card" id="user-activity">
            <p class="small muted">Loading...</p>
          </div>
        </div>
      </div>
      
      <!-- Module Policy -->
      <div class="card">
        <div class="hd"><h3>Module Policy</h3></div>
        <div class="bd">
          <p class="small m0" style="margin-bottom:16px;">Control which features are available system-wide.</p>
          <div id="adminModulesWrap">
            <p class="small muted">Loading module policy...</p>
          </div>
        </div>
      </div>
      
    </div>
  </div>
</div>

<script>
// Disk space check
fetch('/api/system_health.php')
  .then(r => r.json())
  .then(d => {
    if (d.ok && d.disk_free && d.disk_total) {
      const percent = Math.round((d.disk_free / d.disk_total) * 100);
      document.getElementById('disk-space').innerHTML = `${percent}% Free (${d.disk_free_gb} GB)`;
    }
  });

// Test TVMaze connection
async function testTVMaze() {
  const status = document.getElementById('tvmaze-test');
  const badge = document.getElementById('tvmaze-status');
  status.innerHTML = '<span class="small muted">Testing...</span>';
  
  try {
    const resp = await fetch('https://api.tvmaze.com/schedule?date=' + new Date().toISOString().split('T')[0], {
      signal: AbortSignal.timeout(5000)
    });
    
    if (resp.ok) {
      status.innerHTML = '<span class="small" style="color:#0f0;">✓ API responding normally</span>';
      badge.className = 'status-badge status-ok';
      badge.textContent = 'ACTIVE';
    } else {
      status.innerHTML = '<span class="small" style="color:#f00;">✗ API returned HTTP ' + resp.status + '</span>';
      badge.className = 'status-badge status-error';
      badge.textContent = 'ERROR';
    }
  } catch (err) {
    status.innerHTML = '<span class="small" style="color:#f00;">✗ Connection failed: ' + err.message + '</span>';
    badge.className = 'status-badge status-error';
    badge.textContent = 'OFFLINE';
  }
}

// Save Trakt configuration
async function saveTraktConfig() {
  const clientId = document.getElementById('trakt-client-id').value.trim();
  const clientSecret = document.getElementById('trakt-client-secret').value.trim();
  const status = document.getElementById('trakt-save-status');
  const badge = document.getElementById('trakt-status');
  
  if (!clientId || !clientSecret) {
    status.innerHTML = '<span class="small" style="color:#f00;">Both Client ID and Secret are required</span>';
    return;
  }
  
  status.innerHTML = '<span class="small muted">Saving...</span>';
  
  try {
    const resp = await fetch('/api/admin_trakt_config.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({client_id: clientId, client_secret: clientSecret})
    });
    
    const data = await resp.json();
    
    if (data.ok) {
      status.innerHTML = '<span class="small" style="color:#0f0;">✓ Saved successfully</span>';
      badge.className = 'status-badge status-ok';
      badge.textContent = 'CONFIGURED';
    } else {
      status.innerHTML = '<span class="small" style="color:#f00;">✗ Save failed: ' + (data.error || 'unknown') + '</span>';
    }
  } catch (err) {
    status.innerHTML = '<span class="small" style="color:#f00;">✗ Request failed: ' + err.message + '</span>';
  }
}

// Check database integrity
async function checkIntegrity() {
  const results = document.getElementById('integrity-results');
  results.innerHTML = '<p class="small muted">Checking database integrity...</p>';
  
  const resp = await fetch('/api/admin_integrity.php');
  const data = await resp.json();
  
  if (!data.ok) {
    results.innerHTML = '<p class="small" style="color:#f00;">✗ Check failed: ' + (data.error || 'unknown') + '</p>';
    return;
  }
  
  let html = '<div style="margin-top:12px;">';
  
  if (data.issues && data.issues.length > 0) {
    html += '<p class="small" style="color:#fa0;"><strong>⚠ ' + data.issues.length + ' issues found:</strong></p>';
    html += '<ul class="small" style="margin:8px 0;padding-left:20px;">';
    data.issues.forEach(issue => {
      html += '<li>' + issue + '</li>';
    });
    html += '</ul>';
  } else {
    html += '<p class="small" style="color:#0f0;">✓ Database integrity looks good!</p>';
  }
  
  if (data.stats) {
    html += '<details style="margin-top:12px;"><summary class="small">Show Details</summary>';
    html += '<pre class="small" style="margin:8px 0;padding:8px;background:rgba(0,0,0,0.3);border-radius:6px;overflow-x:auto;">';
    html += JSON.stringify(data.stats, null, 2);
    html += '</pre></details>';
  }
  
  html += '</div>';
  results.innerHTML = html;
}

// Load user activity
fetch('/api/admin_activity.php')
  .then(r => r.json())
  .then(d => {
    if (d.ok && d.activity) {
      let html = '';
      d.activity.forEach(a => {
        html += '<div class="stat-row">';
        html += '<span class="stat-label">' + a.label + '</span>';
        html += '<span class="stat-value">' + a.count + '</span>';
        html += '</div>';
      });
      document.getElementById('user-activity').innerHTML = html;
    }
  });

// Import progress monitoring
let progressInterval = null;

function refreshImportStatus() {
  fetch('/api/import_status.php')
    .then(r => r.json())
    .then(d => {
      const container = document.getElementById('import-progress');
      
      if (d.ok && d.in_progress) {
        container.innerHTML = `
          <div style="padding:12px;border:1px solid rgba(0,150,255,0.3);border-radius:8px;background:rgba(0,150,255,0.05);">
            <p class="small" style="margin:0 0 8px;"><strong>${d.type} Import Running</strong></p>
            <div style="background:rgba(255,255,255,0.1);height:8px;border-radius:4px;overflow:hidden;">
              <div style="background:linear-gradient(90deg, #0096ff, #00ff88);height:100%;width:${d.progress || 0}%;transition:width 0.5s;"></div>
            </div>
            <p class="small muted" style="margin:8px 0 0;">${d.status || 'Processing...'} (${d.progress || 0}%)</p>
          </div>
        `;
        
        // Auto-refresh every 2 seconds
        if (!progressInterval) {
          progressInterval = setInterval(refreshImportStatus, 2000);
        }
      } else {
        container.innerHTML = '<p class="small muted">No imports currently running</p>';
        if (progressInterval) {
          clearInterval(progressInterval);
          progressInterval = null;
        }
      }
    });
}

// Initial status check
refreshImportStatus();

// Load module policy (existing code from old admin.php)
(async function() {
  const wrap = document.getElementById('adminModulesWrap');
  if (!wrap) return;
  
  try {
    const resp = await fetch('/api/admin_modules_get.php');
    const data = await resp.json();
    
    if (!data.ok) {
      wrap.innerHTML = '<p class="small error">Failed to load: ' + (data.error || 'unknown') + '</p>';
      return;
    }
    
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
    wrap.innerHTML = '<p class="small error">Error: ' + err.message + '</p>';
  }
})();
</script>
