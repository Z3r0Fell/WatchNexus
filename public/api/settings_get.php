<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

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

$pdo = db();

try {
  // Ensure row exists (settings_json is LONGTEXT you added)
  $pdo->prepare("
    INSERT INTO user_settings (user_id, settings_json)
    VALUES (?, ?)
    ON DUPLICATE KEY UPDATE user_id = user_id
  ")->execute([$uid, json_encode(new stdClass())]);

  $st = $pdo->prepare("SELECT settings_json FROM user_settings WHERE user_id = ? LIMIT 1");
  $st->execute([$uid]);

  $row = $st->fetch(PDO::FETCH_ASSOC);
  $settings = [];

  if ($row && !empty($row['settings_json'])) {
    $tmp = json_decode((string)$row['settings_json'], true);
    if (is_array($tmp)) $settings = $tmp;
  }

  // Global forced modules (admin can override)
  $forced = [
    // safe defaults if module_policy missing
    'browse'   => 'on',
    'calendar' => 'on',
    'settings' => 'on',
    'themes'   => 'on',
  ];

  try {
    $q = $pdo->query("SELECT module_key, forced FROM module_policy");
    while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
      $k = (string)($r['module_key'] ?? '');
      $v = (string)($r['forced'] ?? 'none');
      if ($k !== '' && in_array($v, ['on','off','none'], true)) {
        $forced[$k] = $v;
      }
    }
  } catch (Throwable $e) {
    // module_policy not created yet; defaults remain
  }

  echo json_encode([
    'ok' => true,
    'hit' => 'settings_get.php',
    'user_id' => $uid,
    'settings' => $settings,
    'modules' => [
      'forced' => $forced
    ]
  ], JSON_PRETTY_PRINT);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok'=>false,
    'where'=>'settings_get.php',
    'error'=>$e->getMessage(),
  ], JSON_PRETTY_PRINT);
}
