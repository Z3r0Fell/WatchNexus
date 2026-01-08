<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

$u = current_user();
if (!$u) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'error'=>'Login required']);
  exit;
}

if (!has_role('admin')) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'error'=>'Admin only']);
  exit;
}

$raw = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);

if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'Invalid JSON']);
  exit;
}

// Accept either format: {forced: {id: 'on'}} OR {policies: {id: {forced: 'on'}}}
$forcedMap = [];
if (isset($data['forced']) && is_array($data['forced'])) {
  $forcedMap = $data['forced'];
} else if (isset($data['policies']) && is_array($data['policies'])) {
  foreach ($data['policies'] as $id => $pol) {
    $forcedMap[$id] = is_array($pol) ? ($pol['forced'] ?? 'none') : $pol;
  }
} else {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'Missing forced or policies map']);
  exit;
}

$pdo = db();

try {
  // Ensure table exists (should be created by migration, but just in case)
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS module_policy (
      module_id VARCHAR(64) NOT NULL,
      force_enabled TINYINT(1) NOT NULL DEFAULT 0,
      enabled_by_default TINYINT(1) NOT NULL DEFAULT 1,
      disabled_globally TINYINT(1) NOT NULL DEFAULT 0,
      updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (module_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  ");

  $st = $pdo->prepare("
    INSERT INTO module_policy (module_id, force_enabled, enabled_by_default, disabled_globally)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE 
      force_enabled = VALUES(force_enabled),
      enabled_by_default = VALUES(enabled_by_default),
      disabled_globally = VALUES(disabled_globally)
  ");

  foreach ($forcedMap as $moduleId => $forced) {
    $moduleId = trim((string)$moduleId);
    if ($moduleId === '' || strlen($moduleId) > 64) continue;

    // $forced can be string 'on'/'off'/'none' or array {forced: 'on', enabled_by_default: true}
    if (is_array($forced)) {
      $forcedState = (string)($forced['forced'] ?? 'none');
      $enabledByDefault = !empty($forced['enabled_by_default']) ? 1 : 0;
    } else {
      $forcedState = (string)$forced;
      $enabledByDefault = 1; // default
    }
    
    // Map forced state to proper columns
    $forceEnabled = 0;
    $disabledGlobally = 0;
    
    if ($forcedState === 'on') {
      $forceEnabled = 1;
    } else if ($forcedState === 'off') {
      $disabledGlobally = 1;
    }
    
    $st->execute([$moduleId, $forceEnabled, $enabledByDefault, $disabledGlobally]);
  }

  echo json_encode(['ok'=>true], JSON_PRETTY_PRINT);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok'=>false,
    'error'=>$e->getMessage()
  ], JSON_PRETTY_PRINT);
}
