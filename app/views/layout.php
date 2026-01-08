<?php
declare(strict_types=1);

/**
 * layout.php
 * - Must not output anything before DOCTYPE except intentional markup.
 * - If you see random SQL at the top of the page, it is NOT this file unless the SQL is literally pasted above this PHP tag.
 */

$title = $title ?? 'WatchNexus';
$content = $content ?? '';
$u = function_exists('current_user') ? current_user() : null;

?>
<!doctype html>
<html lang="en" data-theme="grid_noir" data-mode="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>

  <link rel="stylesheet" href="/assets/css/base.css?v=3">
  <link rel="stylesheet" href="/assets/css/calendar.css?v=3">
  <link rel="stylesheet" href="/assets/css/theme.css?v=3">
  <link rel="stylesheet" href="/assets/css/modes.css?v=3">

</head>
<body>
  <header class="topbar">
    <div class="row" style="align-items:center;gap:12px;">
      <div class="logoDot"></div>
      <div>
        <div class="brand">WatchNexus</div>
        <div class="small muted">Ultimate tracker • calendar-first • integrations-ready</div>
      </div>
      <div class="spacer"></div>

      <button class="btn" type="button" id="themeBtn">Theme</button>
      
      <?php if ($u): ?>
        <select id="uiModeSelect" class="input" style="padding:8px 12px;border-radius:6px;border:1px solid rgba(255,255,255,0.14);background:rgba(255,255,255,0.06);color:inherit;cursor:pointer;">
          <option value="command">⚡ Command</option>
          <option value="overview">📊 Overview</option>
          <option value="signal">📡 Signal</option>
          <option value="nebula">🌌 Nebula</option>
        </select>
      <?php endif; ?>

      <?php if ($u): ?>
        <span class="badge">Signed in as <strong><?= htmlspecialchars($u['display_name'] ?? $u['email'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></strong></span>
        <a class="btn" href="/?page=logout">Logout</a>
      <?php else: ?>
        <a class="btn" href="/?page=login">Login</a>
        <a class="btn" href="/?page=register">Register</a>
      <?php endif; ?>
    </div>

    <nav class="tabs" style="margin-top:10px;">
      <a class="tab" href="/?page=calendar">Calendar</a>
      <a class="tab" href="/?page=browse">Browse</a>
      <?php if ($u): ?>
        <a class="tab" href="/?page=settings">Settings</a>
        <?php if (function_exists('has_role') && has_role('mod')): ?>
          <a class="tab" href="/?page=mod">Mod Tools</a>
        <?php endif; ?>
        <?php if (function_exists('has_role') && has_role('admin')): ?>
          <a class="tab" href="/?page=admin">Admin</a>
        <?php endif; ?>
      <?php endif; ?>
    </nav>
  </header>

  <main id="appContent">
    <?= $content ?>
  </main>

  <!-- Theme Modal -->
  <div id="themeModal" class="modal" aria-hidden="true" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:9999;align-items:center;justify-content:center;">
    <div class="modal-content" style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:16px;padding:24px;max-width:500px;width:90%;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h2 style="margin:0;">Theme Settings</h2>
        <button id="themeClose" class="btn" style="padding:4px 12px;">✕</button>
      </div>
      
      <div style="margin-bottom:16px;">
        <label style="display:block;margin-bottom:8px;font-weight:500;">Theme</label>
        <select id="themeSelect" class="input" style="width:100%;padding:10px;border-radius:8px;border:1px solid var(--border);background:var(--bg);color:inherit;">
          <!-- Options populated by theme.js -->
        </select>
      </div>
      
      <div style="margin-bottom:16px;">
        <label style="display:block;margin-bottom:8px;font-weight:500;">Mode</label>
        <select id="modeSelect" class="input" style="width:100%;padding:10px;border-radius:8px;border:1px solid var(--border);background:var(--bg);color:inherit;">
          <option value="system">System</option>
          <option value="light">Light</option>
          <option value="dark">Dark</option>
        </select>
      </div>
      
      <div style="margin-bottom:24px;">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
          <input type="checkbox" id="fxScanlines">
          <span>Scanline Effect</span>
        </label>
      </div>
      
      <div style="display:flex;gap:12px;">
        <button id="applyTheme" class="btn primary" style="flex:1;">Apply</button>
        <button id="resetTheme" class="btn">Reset</button>
      </div>
    </div>
  </div>

  <script src="/assets/js/app.js?v=3"></script>
  <script src="/assets/js/theme.js?v=3"></script>
  <script src="/assets/js/theme-modes.js?v=3"></script>
  <script src="/assets/js/settings.js?v=3"></script>
  
  <script>
  // UI Mode Selector Handler
  (function() {
    const modeSelect = document.getElementById('uiModeSelect');
    if (modeSelect && window.setUIMode) {
      // Set saved mode on load
      const saved = localStorage.getItem('wnx_ui_mode') || 'command';
      modeSelect.value = saved;
      
      // Handle changes
      modeSelect.addEventListener('change', function() {
        window.setUIMode(this.value);
      });
    }
  })();
  </script>
</body>
</html>
