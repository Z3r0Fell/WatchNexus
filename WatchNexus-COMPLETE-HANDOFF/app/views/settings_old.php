<?php
declare(strict_types=1);

$u = current_user();
if (!$u) {
  echo '<div class="banner warn"><div class="badge">Login required</div><div><p>Please login to access Settings.</p></div></div>';
  return;
}
?>

<div class="card">
  <div class="hd">
    <h2>Settings</h2>
    <div class="spacer"></div>
    <span class="small">Modules + integrations</span>
  </div>

  <div class="bd">
    <div id="settingsStatus" class="small muted">Loading…</div>

    <div class="mt16 grid2">
      <div class="card">
        <div class="hd"><h2>Modules</h2><div class="spacer"></div><span class="small">Hide what you don’t use</span></div>
        <div class="bd" id="modulesBox">
          <p class="small muted">Loading modules…</p>
        </div>
      </div>

      <div class="card">
        <div class="hd"><h2>Integrations</h2><div class="spacer"></div><span class="small">Optional</span></div>
        <div class="bd" id="integrationsBox">
          <p class="small muted">Loading integrations…</p>
        </div>
      </div>
    </div>

    <!-- Admin Global Policy renders here (admin-only UI injected by settings.js) -->
    <div id="adminModulesWrap"></div>

    <div class="mt16 row">
      <button class="btn primary" id="saveSettings" type="button">Save</button>
      <button class="btn" id="reloadSettings" type="button">Reload</button>
      <span class="small muted" id="saveHint"></span>
    </div>
  </div>
</div>
