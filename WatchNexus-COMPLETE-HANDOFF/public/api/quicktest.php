<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

$u = current_user();
if (!$u) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'Login required']); exit; }
$uid = (int)$u['id'];

$type = (string)($_GET['t'] ?? 'jackett');
$enabled = (int)($_GET['en'] ?? 1);
$base = (string)($_GET['url'] ?? 'http://localhost:9117');
$key = (string)($_GET['key'] ?? 'TESTKEY123');

$valid = ['trakt','seedr','jackett','prowlarr'];
if (!in_array($type, $valid, true)) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'bad type']); exit; }

try {
  $pdo = db();

  // ensure row exists
  $pdo->prepare("
    INSERT INTO user_integrations (user_id, integration_type, api_variant, enabled, config_json, last_test_status)
    VALUES (?, ?, 'auto', ?, '{}', 'never')
    ON DUPLICATE KEY UPDATE integration_type = integration_type
  ")->execute([$uid, $type, $enabled]);

  // update base fields
  $pdo->prepare("
    UPDATE user_integrations
    SET enabled = ?, base_url = ?, api_variant = 'auto'
    WHERE user_id = ? AND integration_type = ?
  ")->execute([$enabled, $base, $uid, $type]);

  // store secret in *_enc using AES-256-GCM bundle
  $enc = wnx_bundle_encrypt($key);

  if ($type === 'jackett' || $type === 'prowlarr') {
    $pdo->prepare("UPDATE user_integrations SET api_key_enc = ? WHERE user_id = ? AND integration_type = ?")
        ->execute([$enc, $uid, $type]);
  } else {
    // seedr/trakt just park in config for now
    $pdo->prepare("UPDATE user_integrations SET config_json = ? WHERE user_id = ? AND integration_type = ?")
        ->execute([json_encode(['note'=>'quicktest ran'], JSON_UNESCAPED_SLASHES), $uid, $type]);
  }

  echo json_encode([
    'ok' => true,
    'saved' => [
      'integration_type' => $type,
      'enabled' => (bool)$enabled,
      'base_url' => $base,
      'stored_api_key' => ($type === 'jackett' || $type === 'prowlarr')
    ]
  ], JSON_PRETTY_PRINT);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_PRETTY_PRINT);
}
