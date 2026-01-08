<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$page = $_GET['page'] ?? 'calendar';
$title = 'WatchNexus • ' . ucfirst((string)$page);

$partial = (isset($_GET['partial']) && $_GET['partial'] === '1');

if ($page === 'logout') {
  // Keep classic logout as a fallback (SPA uses /api/auth.php).
  logout_user();
  if ($partial) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<div class="banner"><div class="badge">Logged out</div><div><p>See you next episode.</p></div></div>';
    exit;
  }
  header('Location: ?page=calendar');
  exit;
}

$viewMap = [
  'calendar' => __DIR__ . '/../app/views/calendar.php',
  'browse' => __DIR__ . '/../app/views/browse.php',
  'myshows' => __DIR__ . '/../app/views/myshows.php',
  'settings' => __DIR__ . '/../app/views/settings.php',
  'login' => __DIR__ . '/../app/views/login.php',
  'register' => __DIR__ . '/../app/views/register.php',
  'mod' => __DIR__ . '/../app/views/mod.php',
  'admin' => __DIR__ . '/../app/views/admin.php',
];

// Module gating (hard enforcement)
$pageToModule = [
  'calendar' => 'calendar',
  'browse' => 'browse',
  'myshows' => 'myshows',
  'settings' => 'settings',
  'mod' => 'mod',
  'admin' => 'admin',
];

if (isset($pageToModule[$page])) {
  $mid = $pageToModule[$page];
  if (!wnx_module_enabled($mid)) {
    http_response_code(current_user() ? 403 : 401);
    $content = '<div class="banner"><div class="badge">Blocked</div><div><p>This feature is not available for your account right now.</p></div></div>';
    if ($partial) {
      header('Content-Type: text/html; charset=utf-8');
      echo $content;
      exit;
    }
    require __DIR__ . '/../app/views/layout.php';
    exit;
  }
}

ob_start();
if (isset($viewMap[$page])) {
  require $viewMap[$page];
} else {
  http_response_code(404);
  echo '<div class="card"><div class="hd"><h2>404</h2></div><div class="bd"><p>Page not found.</p></div></div>';
}
$content = ob_get_clean();

if ($partial) {
  header('Content-Type: text/html; charset=utf-8');
  header('X-WNX-Title: ' . $title);
  echo $content;
  exit;
}

require __DIR__ . '/../app/views/layout.php';
