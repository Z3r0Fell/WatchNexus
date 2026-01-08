<?php
declare(strict_types=1);

$u = current_user();
if (!$u) {
  echo '<div class="banner warn"><div class="badge">Login required</div><div><p>Please login to access Settings.</p></div></div>';
  return;
}
?>

<style>
.accordion {
  margin-top: 16px;
}
.accordion-item {
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 12px;
  margin-bottom: 12px;
  overflow: hidden;
  background: rgba(255,255,255,0.02);
}
.accordion-header {
  background: rgba(255,255,255,0.05);
  padding: 16px 20px;
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
  user-select: none;
  transition: background 0.2s;
}
.accordion-header:hover {
  background: rgba(255,255,255,0.08);
}
.accordion-header h3 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
}
.accordion-icon {
  transition: transform 0.2s;
  font-size: 1.2rem;
}
.accordion-item.open .accordion-icon {
  transform: rotate(180deg);
}
.accordion-content {
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.3s ease-out;
}
.accordion-item.open .accordion-content {
  max-height: 5000px;
  transition: max-height 0.5s ease-in;
}
.accordion-body {
  padding: 20px;
}
</style>

<div class="card">
  <div class="hd">
    <h2>Settings</h2>
    <div class="spacer"></div>
    <span class="small">Configure your experience</span>
  </div>

  <div class="bd">
    <div id="settingsStatus" class="small muted">Loading…</div>

    <div class="accordion">
      <!-- Modules Section -->
      <div class="accordion-item open">
        <div class="accordion-header" onclick="this.parentElement.classList.toggle('open')">
          <div>
            <h3>Modules</h3>
            <p class="small muted" style="margin:4px 0 0;">Show or hide features</p>
          </div>
          <span class="accordion-icon">▼</span>
        </div>
        <div class="accordion-content">
          <div class="accordion-body" id="modulesBox">
            <p class="small muted">Loading modules…</p>
          </div>
        </div>
      </div>

      <!-- Integrations Section -->
      <div class="accordion-item">
        <div class="accordion-header" onclick="this.parentElement.classList.toggle('open')">
          <div>
            <h3>Integrations</h3>
            <p class="small muted" style="margin:4px 0 0;">Connect external services</p>
          </div>
          <span class="accordion-icon">▼</span>
        </div>
        <div class="accordion-content">
          <div class="accordion-body" id="integrationsBox">
            <p class="small muted">Loading integrations…</p>
          </div>
        </div>
      </div>

      <!-- Trakt Section -->
      <div class="accordion-item">
        <div class="accordion-header" onclick="this.parentElement.classList.toggle('open')">
          <div>
            <h3>Trakt</h3>
            <p class="small muted" style="margin:4px 0 0;">Sync watched shows & tracking</p>
          </div>
          <span class="accordion-icon">▼</span>
        </div>
        <div class="accordion-content">
          <div class="accordion-body" id="traktBox">
            <div id="trakt-status">
              <p class="small muted">Checking Trakt connection...</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Admin Module Policy (admin only, rendered by settings.js) -->
      <div id="adminModulesWrap"></div>
    </div>

    <div class="mt16 row">
      <button class="btn primary" id="saveSettings" type="button">Save All Changes</button>
      <button class="btn" id="reloadSettings" type="button">Reload</button>
      <span class="small muted" id="saveHint" style="margin-left:auto;"></span>
    </div>
  </div>
</div>

<script>
// Check if Trakt connected message
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('trakt_connected') === '1') {
  const banner = document.createElement('div');
  banner.className = 'banner success';
  banner.style.marginBottom = '16px';
  banner.innerHTML = '<div class="badge">✓ Success</div><div><p>Trakt connected successfully! Your shows will now sync.</p></div>';
  document.querySelector('.card .bd').prepend(banner);
  
  // Remove param from URL
  window.history.replaceState({}, '', '/?page=settings');
}

// Load Trakt status
async function loadTraktStatus() {
  const traktBox = document.getElementById('trakt-status');
  if (!traktBox) return;
  
  try {
    const resp = await fetch('/api/integrations_get.php');
    const data = await resp.json();
    
    if (!data.ok) {
      traktBox.innerHTML = '<p class="error">Failed to load Trakt status</p>';
      return;
    }
    
    const traktIntegration = (data.integrations || []).find(i => i.integration_type === 'trakt');
    
    if (traktIntegration && traktIntegration.enabled) {
      // Connected
      traktBox.innerHTML = `
        <div style="padding:16px;background:rgba(0,255,0,0.1);border:1px solid rgba(0,255,0,0.3);border-radius:10px;margin-bottom:16px;">
          <p class="small" style="margin:0 0 12px;"><strong>✓ Trakt Connected</strong></p>
          <p class="small muted" style="margin:0 0 16px;">Your Trakt account is linked. You can sync your watched shows.</p>
          <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button class="btn primary small" id="trakt-sync-btn">Sync Watched Shows</button>
            <button class="btn small" id="trakt-disconnect-btn">Disconnect</button>
          </div>
          <div id="trakt-sync-status" style="margin-top:12px;"></div>
        </div>
      `;
      
      // Sync button
      document.getElementById('trakt-sync-btn').addEventListener('click', async function() {
        const btn = this;
        const status = document.getElementById('trakt-sync-status');
        
        btn.disabled = true;
        btn.textContent = 'Syncing...';
        status.innerHTML = '<p class="small muted">Importing your watched shows from Trakt...</p>';
        
        try {
          const resp = await fetch('/api/trakt_sync.php');
          const data = await resp.json();
          
          if (data.ok) {
            status.innerHTML = `
              <div style="padding:12px;background:rgba(0,255,0,0.1);border:1px solid rgba(0,255,0,0.3);border-radius:8px;">
                <p class="small" style="margin:0;"><strong>✓ Sync Complete!</strong></p>
                <p class="small" style="margin:8px 0 0;">${data.matched} shows tracked from Trakt</p>
                ${data.unmatched && data.unmatched.length > 0 ? `
                  <details style="margin-top:8px;">
                    <summary class="small">Unmatched shows (${data.unmatched.length})</summary>
                    <ul class="small" style="margin:4px 0;padding-left:18px;">
                      ${data.unmatched.map(s => '<li>' + s + '</li>').join('')}
                    </ul>
                  </details>
                ` : ''}
              </div>
            `;
          } else {
            status.innerHTML = '<p class="error">Sync failed: ' + (data.error || 'Unknown error') + '</p>';
          }
        } catch (err) {
          status.innerHTML = '<p class="error">Error: ' + err.message + '</p>';
        } finally {
          btn.disabled = false;
          btn.textContent = 'Sync Watched Shows';
        }
      });
      
      // Disconnect button
      document.getElementById('trakt-disconnect-btn').addEventListener('click', async function() {
        if (!confirm('Disconnect Trakt? Your tracked shows will remain.')) return;
        
        const btn = this;
        btn.disabled = true;
        btn.textContent = 'Disconnecting...';
        
        try {
          const resp = await fetch('/api/integrations_save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              integrations: [{ integration_type: 'trakt', enabled: false }]
            })
          });
          
          const data = await resp.json();
          
          if (data.ok) {
            loadTraktStatus();
          } else {
            alert('Failed to disconnect: ' + (data.error || 'Unknown error'));
            btn.disabled = false;
            btn.textContent = 'Disconnect';
          }
        } catch (err) {
          alert('Error: ' + err.message);
          btn.disabled = false;
          btn.textContent = 'Disconnect';
        }
      });
      
    } else {
      // Not connected
      traktBox.innerHTML = `
        <div style="padding:16px;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.1);border-radius:10px;">
          <p class="small" style="margin:0 0 12px;"><strong>Trakt Not Connected</strong></p>
          <p class="small muted" style="margin:0 0 16px;">Connect your Trakt account to sync your watched shows and tracking data.</p>
          <a href="/api/oauth_trakt_start.php" class="btn primary">Connect Trakt</a>
        </div>
      `;
    }
    
  } catch (err) {
    console.error('Trakt status error:', err);
    traktBox.innerHTML = '<p class="error">Error loading Trakt status: ' + err.message + '</p>';
  }
}

// Load Trakt status on page load
loadTraktStatus();
</script>
