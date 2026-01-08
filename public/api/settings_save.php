<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false,'error'=>'POST required']);
  exit;
}

$u = current_user();
if (!$u) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'error'=>'Login required']);
  exit;
}

$uid = (int)($u['id'] ?? 0);
if ($uid <= 0) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'error'=>'Invalid session user']);
  exit;
}

$raw = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);
if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'Invalid JSON']);
  exit;
}

$pdo = db();

try {
  // Load current
  $pdo->prepare("
    INSERT INTO user_settings (user_id, settings_json)
    VALUES (?, ?)
    ON DUPLICATE KEY UPDATE user_id = user_id
  ")->execute([$uid, json_encode(new stdClass())]);

  $st = $pdo->prepare("SELECT settings_json FROM user_settings WHERE user_id = ? LIMIT 1");
  $st->execute([$uid]);
  $row = $st->fetch(PDO::FETCH_ASSOC);

  $current = [];
  if ($row && !empty($row['settings_json'])) {
    $tmp = json_decode((string)$row['settings_json'], true);
    if (is_array($tmp)) $current = $tmp;
  }

  // Apply allowed keys
  $modules = $data['modules'] ?? null;
  if (is_array($modules)) {
    // Only accept booleans for known module keys
    $known = ['browse','myshows','trakt','seedr','jackett','prowlarr','calendar','settings','themes','mod','admin'];
    $clean = $current['modules'] ?? [];
    if (!is_array($clean)) $clean = [];

    foreach ($known as $k) {
      if (array_key_exists($k, $modules)) {
        $clean[$k] = (bool)$modules[$k];
      }
    }

    $current['modules'] = $clean;
  }

  // Theme keys can live here later (kept compatible)
  if (isset($data['theme']) && is_array($data['theme'])) {
    $current['theme'] = $data['theme'];
  }

  $json = json_encode($current, JSON_UNESCAPED_SLASHES);

  $upd = $pdo->prepare("UPDATE user_settings SET settings_json = ? WHERE user_id = ?");
  $upd->execute([$json, $uid]);

  echo json_encode(['ok'=>true], JSON_PRETTY_PRINT);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok'=>false,
    'where'=>'settings_save.php',
    'error'=>$e->getMessage(),
  ], JSON_PRETTY_PRINT);
}
