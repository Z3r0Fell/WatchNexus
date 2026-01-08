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

$type = strtolower(trim((string)($data['integration_type'] ?? '')));
$allowed = ['trakt','seedr','jackett','prowlarr'];
if (!in_array($type, $allowed, true)) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'Invalid integration_type']);
  exit;
}

$enabled = !empty($data['enabled']) ? 1 : 0;
$api_variant = (string)($data['api_variant'] ?? 'auto');
if (!in_array($api_variant, ['auto','v1','v2'], true)) $api_variant = 'auto';

$base_url = trim((string)($data['base_url'] ?? ''));
if ($base_url !== '' && strlen($base_url) > 512) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'base_url too long']);
  exit;
}

$config = $data['config'] ?? [];
if (!is_array($config)) $config = [];
$config_json = json_encode($config, JSON_UNESCAPED_SLASHES);

$secrets = $data['secrets'] ?? [];
if (!is_array($secrets)) $secrets = [];

$pdo = db();

try {
  // Ensure row exists
  $ins = $pdo->prepare("
    INSERT INTO user_integrations
      (user_id, provider, integration_type, api_variant, enabled, base_url, config_json, last_test_status)
    VALUES
      (?, ?, ?, ?, ?, ?, ?, 'never')
    ON DUPLICATE KEY UPDATE
      api_variant = VALUES(api_variant),
      enabled     = VALUES(enabled),
      base_url    = VALUES(base_url),
      config_json = VALUES(config_json)
  ");
  $ins->execute([$uid, $type, $type, $api_variant, $enabled, ($base_url===''?null:$base_url), $config_json]);

  // Update encrypted secrets only if provided
  if ($type === 'jackett' || $type === 'prowlarr') {
    $k = trim((string)($secrets['api_key'] ?? ''));
    if ($k !== '') {
      $upd = $pdo->prepare("UPDATE user_integrations SET api_key_enc = ? WHERE user_id = ? AND integration_type = ?");
      $upd->execute([encrypt_secret($k), $uid, $type]);
    }
  }

  if ($type === 'seedr') {
    $user = trim((string)($secrets['username'] ?? ''));
    $pass = (string)($secrets['password'] ?? '');
    if ($user !== '') {
      $upd = $pdo->prepare("UPDATE user_integrations SET username_enc = ? WHERE user_id = ? AND integration_type = ?");
      $upd->execute([encrypt_secret($user), $uid, $type]);
    }
    if ($pass !== '') {
      $upd = $pdo->prepare("UPDATE user_integrations SET password_enc = ? WHERE user_id = ? AND integration_type = ?");
      $upd->execute([encrypt_secret($pass), $uid, $type]);
    }
  }

  if ($type === 'trakt') {
    // client_id can be stored in config_json (non-secret), client_secret should be encrypted
    $clientSecret = (string)($secrets['client_secret'] ?? '');
    if ($clientSecret !== '') {
      $upd = $pdo->prepare("UPDATE user_integrations SET password_enc = ? WHERE user_id = ? AND integration_type = ?");
      $upd->execute([encrypt_secret($clientSecret), $uid, $type]);
    }
  }

  echo json_encode(['ok'=>true], JSON_PRETTY_PRINT);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok'=>false,
    'where'=>'integrations_save.php',
    'error'=>$e->getMessage(),
  ], JSON_PRETTY_PRINT);
}
