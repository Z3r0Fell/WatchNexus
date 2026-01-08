<?php
declare(strict_types=1);
require_role_html('mod');
?>
<div class="card">
  <div class="hd"><h2>Mod Tools</h2><div class="spacer"></div><span class="small">RBAC-gated</span></div>
  <div class="bd">
    <p class="m0">This is where importers and catalog maintenance tools live.</p>
    <div class="mt16 grid2">
      <div class="card">
        <div class="hd"><h2>TVMaze Importer</h2></div>
        <div class="bd">
          <p class="small">Import TV show schedules from TVMaze. This will populate your calendar with real airing dates.</p>
          
          <div style="margin-top:14px;">
            <label class="small muted" style="display:block;">Start Date</label>
            <input type="date" id="tvmaze-start" value="<?= date('Y-m-d') ?>" style="width:100%;padding:8px;margin-top:4px;border-radius:8px;border:1px solid rgba(255,255,255,0.14);background:rgba(255,255,255,0.06);color:inherit;">
          </div>
          
          <div style="margin-top:14px;">
            <label class="small muted" style="display:block;">End Date</label>
            <input type="date" id="tvmaze-end" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" style="width:100%;padding:8px;margin-top:4px;border-radius:8px;border:1px solid rgba(255,255,255,0.14);background:rgba(255,255,255,0.06);color:inherit;">
          </div>
          
          <div style="margin-top:14px;">
            <label class="small muted" style="display:block;">Country</label>
            <select id="tvmaze-country" style="width:100%;padding:8px;margin-top:4px;border-radius:8px;border:1px solid rgba(255,255,255,0.14);background:rgba(255,255,255,0.06);color:inherit;">
              <option value="US">United States</option>
              <option value="GB">United Kingdom</option>
              <option value="CA">Canada</option>
              <option value="AU">Australia</option>
            </select>
          </div>
          
          <div style="margin-top:16px;">
            <button class="btn primary" id="run-tvmaze-import" type="button">Start Import</button>
            <div id="tvmaze-status" style="margin-top:12px;"></div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="hd"><h2>Sonarr Import</h2></div>
        <div class="bd">
          <p class="small">Upload your Sonarr backup to automatically track all monitored shows.</p>
          
          <div style="margin-top:16px;padding:14px;border:1px solid rgba(255,165,0,0.3);border-radius:8px;background:rgba(255,165,0,0.05);">
            <p class="small" style="margin:0 0 8px;"><strong>How to export from Sonarr:</strong></p>
            <ol class="small" style="margin:0;padding-left:20px;line-height:1.6;">
              <li>Go to Sonarr → System → Backup</li>
              <li>Click "Backup Now"</li>
              <li>Download the ZIP file (e.g., sonarr_backup_2026.01.08.zip)</li>
              <li>Upload it here</li>
            </ol>
          </div>
          
          <div style="margin-top:16px;">
            <label class="small muted" style="display:block;margin-bottom:8px;">Sonarr Backup ZIP</label>
            <input type="file" id="sonarr-file" accept=".zip" style="width:100%;padding:8px;border-radius:8px;border:1px solid rgba(255,255,255,0.14);background:rgba(255,255,255,0.06);color:inherit;">
          </div>
          
          <div style="margin-top:16px;">
            <button class="btn primary" id="run-sonarr-import" type="button">Import from Sonarr</button>
            <div id="sonarr-status" style="margin-top:12px;"></div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="hd"><h2>Other Importers</h2></div>
        <div class="bd">
          <p class="small m0">Additional import sources (coming soon):</p>
          <ul class="small" style="margin:12px 0 0; padding-left:18px; line-height:1.5">
            <li>AniList anime import</li>
            <li>Trakt sync (user-scoped)</li>
          </ul>
          <div class="mt16 row wrap">
            <button class="btn" type="button" disabled>Run Import (coming soon)</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
console.log('Mod Tools JavaScript loaded');

// TVMaze Import Handler
document.getElementById('run-tvmaze-import')?.addEventListener('click', async function() {
  console.log('TVMaze import button clicked');
  
  const btn = this;
  const status = document.getElementById('tvmaze-status');
  const start = document.getElementById('tvmaze-start').value;
  const end = document.getElementById('tvmaze-end').value;
  const country = document.getElementById('tvmaze-country').value;
  
  console.log('Import params:', {start, end, country});
  
  if (!start || !end) {
    status.innerHTML = '<p class="error">Please select start and end dates.</p>';
    return;
  }
  
  btn.disabled = true;
  btn.textContent = 'Importing...';
  status.innerHTML = '<p class="small muted">Importing from TVMaze... This may take a while.</p>';
  
  try {
    const url = `/api/import_tvmaze.php?start=${start}&end=${end}&country=${country}`;
    console.log('Fetching:', url);
    
    const resp = await fetch(url);
    console.log('Response status:', resp.status);
    
    if (!resp.ok) {
      const errorText = await resp.text();
      console.error('HTTP error:', resp.status, errorText);
      status.innerHTML = `<p class="error"><strong>HTTP ${resp.status}:</strong> ${errorText.substring(0, 200)}</p>`;
      btn.disabled = false;
      btn.textContent = 'Start Import';
      return;
    }
    
    const text = await resp.text();
    console.log('Response text:', text.substring(0, 500));
    
    let data;
    try {
      data = JSON.parse(text);
    } catch (parseErr) {
      console.error('JSON parse error:', parseErr, 'Text:', text);
      status.innerHTML = `<p class="error"><strong>Invalid JSON response:</strong> ${text.substring(0, 200)}</p>`;
      btn.disabled = false;
      btn.textContent = 'Start Import';
      return;
    }
    
    console.log('Parsed data:', data);
    
    if (!data.ok) {
      status.innerHTML = `<p class="error"><strong>Import failed:</strong> ${data.error || 'Unknown error'}</p>`;
      btn.disabled = false;
      btn.textContent = 'Start Import';
      return;
    }
    
    let html = `
      <div class="card" style="margin-top:12px;background:rgba(0,255,0,0.1);border:1px solid rgba(0,255,0,0.3);">
        <div class="bd">
          <p class="small"><strong>Import Complete!</strong></p>
          <ul class="small" style="margin:8px 0 0;padding-left:18px;">
            <li><strong>${data.events_created || 0}</strong> events created</li>
            <li><strong>${data.shows_created || 0}</strong> new shows added</li>
            <li><strong>${data.skipped || 0}</strong> duplicates skipped</li>
            <li>Date range: ${data.date_range || 'N/A'}</li>
          </ul>
    `;
    
    if (data.errors && data.errors.length > 0) {
      html += `
        <details style="margin-top:8px;">
          <summary class="small">Errors (${data.errors.length})</summary>
          <ul class="small" style="margin:4px 0 0;padding-left:18px;">
            ${data.errors.map(e => `<li>${e}</li>`).join('')}
          </ul>
        </details>
      `;
    }
    
    html += `
          <p class="small muted" style="margin-top:12px;">
            Go to <a href="?page=calendar">Calendar</a> to see the imported events!
          </p>
        </div>
      </div>
    `;
    
    status.innerHTML = html;
    btn.disabled = false;
    btn.textContent = 'Start Import';
    
  } catch (err) {
    console.error('TVMaze import error:', err);
    status.innerHTML = `<p class="error"><strong>Request failed:</strong> ${err.message}<br><small>Check browser console (F12) for details</small></p>`;
    btn.disabled = false;
    btn.textContent = 'Start Import';
  }
});

// Sonarr Import Handler
document.getElementById('run-sonarr-import')?.addEventListener('click', async function() {
  console.log('Sonarr import button clicked');
  
  const btn = this;
  const status = document.getElementById('sonarr-status');
  const fileInput = document.getElementById('sonarr-file');
  const file = fileInput.files[0];
  
  console.log('File selected:', file);
  
  if (!file) {
    status.innerHTML = '<p class="error">Please select a Sonarr backup ZIP file first.</p>';
    return;
  }
  
  if (!file.name.endsWith('.zip')) {
    status.innerHTML = '<p class="error">File must be a ZIP archive.</p>';
    return;
  }
  
  btn.disabled = true;
  btn.textContent = 'Uploading...';
  status.innerHTML = '<p class="small muted">Uploading backup file...</p>';
  
  const formData = new FormData();
  formData.append('sonarr_backup', file);
  
  console.log('Uploading file:', file.name, file.size, 'bytes');
  
  try {
    const resp = await fetch('/api/import_sonarr.php', {
      method: 'POST',
      body: formData
    });
    
    console.log('Response status:', resp.status);
    
    if (!resp.ok) {
      const errorText = await resp.text();
      console.error('HTTP error:', resp.status, errorText);
      status.innerHTML = `<p class="error"><strong>HTTP ${resp.status}:</strong> ${errorText.substring(0, 200)}</p>`;
      btn.disabled = false;
      btn.textContent = 'Import from Sonarr';
      return;
    }
    
    const text = await resp.text();
    console.log('Response text:', text.substring(0, 500));
    
    let data;
    try {
      data = JSON.parse(text);
    } catch (parseErr) {
      console.error('JSON parse error:', parseErr);
      status.innerHTML = `<p class="error"><strong>Invalid JSON response:</strong> ${text.substring(0, 200)}</p>`;
      btn.disabled = false;
      btn.textContent = 'Import from Sonarr';
      return;
    }
    
    console.log('Parsed data:', data);
    
    if (!data.ok) {
      status.innerHTML = `<p class="error"><strong>Import failed:</strong> ${data.error || 'Unknown error'}</p>`;
      btn.disabled = false;
      btn.textContent = 'Import from Sonarr';
      return;
    }
    
    let html = `
      <div class="card" style="margin-top:12px;background:rgba(0,255,0,0.1);border:1px solid rgba(0,255,0,0.3);">
        <div class="bd">
          <p class="small"><strong>Sonarr Import Complete!</strong></p>
          <ul class="small" style="margin:8px 0 0;padding-left:18px;">
            <li><strong>${data.tracked || 0}</strong> shows auto-tracked</li>
            <li><strong>${data.matched || 0}</strong> shows matched in database</li>
            <li><strong>${data.total_monitored || 0}</strong> monitored shows in Sonarr</li>
          </ul>
    `;
    
    if (data.unmatched && data.unmatched.length > 0) {
      html += `
        <details style="margin-top:12px;">
          <summary class="small">Unmatched Shows (${data.unmatched.length})</summary>
          <ul class="small" style="margin:4px 0 0;padding-left:18px;">
            ${data.unmatched.map(s => `<li>${s}</li>`).join('')}
          </ul>
          <p class="small muted" style="margin-top:8px;">These shows aren't in our database yet. Import TV schedule from TVMaze first.</p>
        </details>
      `;
    }
    
    html += `
          <p class="small muted" style="margin-top:12px;">
            Go to <a href="?page=myshows">My Shows</a> to see your tracked shows!
          </p>
        </div>
      </div>
    `;
    
    status.innerHTML = html;
    btn.disabled = false;
    btn.textContent = 'Import from Sonarr';
    fileInput.value = '';
    
  } catch (err) {
    console.error('Sonarr import error:', err);
    status.innerHTML = `<p class="error"><strong>Request failed:</strong> ${err.message}<br><small>Check browser console (F12) for details</small></p>`;
    btn.disabled = false;
    btn.textContent = 'Import from Sonarr';
  }
});

console.log('Mod Tools JavaScript complete');
</script>
