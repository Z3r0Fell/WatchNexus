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
