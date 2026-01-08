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
  // Seed default rows if missing (NO output, and uses positional placeholders to avoid HY093)
  $seedSql = "
    INSERT INTO user_integrations
      (user_id, provider, integration_type, api_variant, enabled, last_test_status)
    VALUES
      (?, 'trakt',   'trakt',   'auto', 0, 'never'),
      (?, 'seedr',   'seedr',   'auto', 0, 'never'),
      (?, 'jackett', 'jackett', 'auto', 0, 'never'),
      (?, 'prowlarr','prowlarr','auto', 0, 'never')
    ON DUPLICATE KEY UPDATE user_id = user_id
  ";
  $stSeed = $pdo->prepare($seedSql);
  $stSeed->execute([$uid, $uid, $uid, $uid]);

  $st = $pdo->prepare("
    SELECT
      integration_type,
      api_variant,
      enabled,
      base_url,
      config_json,
      last_test_status,
      last_test_message,
      last_test_at,
      api_key_enc,
      username_enc,
      password_enc,
      access_token_enc,
      refresh_token_enc,
      token_expires_at
    FROM user_integrations
    WHERE user_id = ?
  ");
  $st->execute([$uid]);

  $out = [];
  while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    $type = (string)($r['integration_type'] ?? '');

    if ($type === '') continue;

    $cfg = new stdClass();
    if (!empty($r['config_json'])) {
      $tmp = json_decode((string)$r['config_json'], true);
      if (is_array($tmp)) $cfg = $tmp;
    }

    $out[$type] = [
      'enabled' => ((int)($r['enabled'] ?? 0) === 1),
      'api_variant' => (string)($r['api_variant'] ?? 'auto'),
      'base_url' => (string)($r['base_url'] ?? ''),
      'config' => $cfg,
      'secrets' => [
        'api_key'       => !empty($r['api_key_enc']),
        'username'      => !empty($r['username_enc']),
        'password'      => !empty($r['password_enc']),
        'access_token'  => !empty($r['access_token_enc']),
        'refresh_token' => !empty($r['refresh_token_enc']),
      ],
      'token_expires_at' => !empty($r['token_expires_at']) ? (string)$r['token_expires_at'] : null,
      'last_test' => [
        'status'  => (string)($r['last_test_status'] ?? 'never'),
        'message' => (string)($r['last_test_message'] ?? ''),
        'at'      => !empty($r['last_test_at']) ? (string)$r['last_test_at'] : null,
      ],
    ];
  }

  echo json_encode(['ok'=>true,'integrations'=>$out], JSON_PRETTY_PRINT);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok'=>false,
    'where'=>'integrations_get.php',
    'error'=>$e->getMessage(),
  ], JSON_PRETTY_PRINT);
}
