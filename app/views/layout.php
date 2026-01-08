<?php
declare(strict_types=1);

/**
 * layout.php
 * - Full-page shell for WatchNexus
 * - Must not output anything before DOCTYPE
 */

$title   = $title ?? 'WatchNexus';
$content = $content ?? '';
$page    = $page ?? ($_GET['page'] ?? 'calendar');
$u       = function_exists('current_user') ? current_user() : null;

?>
<!doctype html>
<html lang="en" data-theme="grid_noir" data-mode="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>

  <link rel="stylesheet" href="/assets/css/base.css?v=4">
  <link rel="stylesheet" href="/assets/css/calendar.css?v=4">
  <link rel="stylesheet" href="/assets/css/themes.css?v=4">
  <link rel="stylesheet" href="/assets/css/modes.css?v=4">
</head>
<body>
  <header class="topbar">
    <div class="container">
      <div class="row wrap" style="gap:12px;">
        <div class="brand" style="padding:0;">
          <div class="mark" aria-hidden="true"></div>
          <div>
            <div class="name">WatchNexus</div>
            <div class="sub">Ultimate tracker • calendar-first • integrations-ready</div>
          </div>
        </div>

        <div class="spacer"></div>

        <button class="btn" type="button" id="themeBtn" hidden>Appearance</button>

        <span class="badge" id="userPill" hidden></span>

        <a class="btn" id="loginBtn" href="/?page=login">Login</a>
        <a class="btn" id="registerBtn" href="/?page=register">Register</a>
        <a class="btn" id="logoutBtn" href="/?page=logout" hidden>Logout</a>
      </div>

      <div id="publicBanner" class="banner mt12" <?= $u ? 'hidden' : '' ?>>
        <div class="badge">Public</div>
        <div>
          <p>Sign in to enable themes, tracking, downloads, and integrations.</p>
        </div>
      </div>

      <nav class="tabs" id="navTabs" style="margin-top:10px;"></nav>
    </div>
  </header>

  <main id="appContent" class="container" data-page="<?= htmlspecialchars((string)$page, ENT_QUOTES, 'UTF-8') ?>">
    <?= $content ?>
  </main>

  <!-- Appearance Modal (Theme + Color Scheme + UI Mode) -->
  <div id="themeModal" class="modal-backdrop" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="appearanceTitle">
      <div class="mh">
        <div class="h" id="appearanceTitle">Appearance</div>
        <div class="spacer"></div>
        <button id="themeClose" class="btn" type="button">✕</button>
      </div>

      <div class="mb">
        <div class="formgrid">
          <div>
            <div class="label">Theme</div>
            <select id="themeSelect" class="input"></select>
          </div>

          <div>
            <div class="label">Color scheme</div>
            <select id="modeSelect" class="input">
              <option value="system">System</option>
              <option value="light">Light</option>
              <option value="dark">Dark</option>
            </select>
          </div>

          <div>
            <div class="label">UI mode</div>
            <select id="uiModeSelect" class="input">
              <option value="command">⚡ Command</option>
              <option value="overview">📊 Overview</option>
              <option value="signal">📡 Signal</option>
              <option value="nebula">🌌 Nebula</option>
            </select>
          </div>

          <div>
            <div class="label">Effects</div>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-top:10px;">
              <input type="checkbox" id="fxScanlines">
              <span class="small muted">Scanlines overlay</span>
            </label>
          </div>
        </div>

        <div class="small muted mt12">
          Tip: <span class="kbd">Command</span> is compact, <span class="kbd">Overview</span> is roomy, <span class="kbd">Signal</span> emphasizes alerts, <span class="kbd">Nebula</span> is cinematic.
        </div>
      </div>

      <div class="ft">
        <button id="applyTheme" class="btn primary" type="button">Apply</button>
        <button id="resetTheme" class="btn" type="button">Reset</button>
      </div>
    </div>
  </div>

  <script src="/assets/js/app.js?v=4"></script>
  <script src="/assets/js/appearance.js?v=4"></script>
  <script src="/assets/js/settings.js?v=4"></script>
</body>
</html>
