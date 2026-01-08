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

$pdo = db();

function http_get(string $url, array $headers = []): array {
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 6);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
  if ($headers) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

  $body = curl_exec($ch);
  $err  = curl_error($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  return [$code, $body === false ? '' : (string)$body, (string)$err];
}

try {
  $st = $pdo->prepare("
    SELECT base_url, api_variant, config_json, api_key_enc, username_enc, password_enc
    FROM user_integrations
    WHERE user_id = ? AND integration_type = ?
    LIMIT 1
  ");
  $st->execute([$uid, $type]);
  $row = $st->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    http_response_code(404);
    echo json_encode(['ok'=>false,'error'=>'Integration row not found']);
    exit;
  }

  $base = trim((string)($row['base_url'] ?? ''));
  $cfg  = [];
  if (!empty($row['config_json'])) {
    $tmp = json_decode((string)$row['config_json'], true);
    if (is_array($tmp)) $cfg = $tmp;
  }

  $status = 'fail';
  $msg = 'Not configured';

  if ($type === 'jackett') {
    $apiKey = decrypt_secret((string)($row['api_key_enc'] ?? ''));
    if ($base === '' || $apiKey === '') {
      $msg = 'Missing base_url or api_key';
    } else {
      $url = rtrim($base, '/') . '/api/v2.0/server/config?apikey=' . urlencode($apiKey);
      [$code, $body, $err] = http_get($url);
      if ($code >= 200 && $code < 300) {
        $j = json_decode($body, true);
        if (is_array($j)) { $status = 'ok'; $msg = 'Jackett API OK'; }
        else { $msg = 'Jackett responded but JSON invalid'; }
      } else {
        $msg = 'Jackett HTTP ' . $code . ($err ? ' (' . $err . ')' : '');
      }
    }
  }

  if ($type === 'prowlarr') {
    $apiKey = decrypt_secret((string)($row['api_key_enc'] ?? ''));
    if ($base === '' || $apiKey === '') {
      $msg = 'Missing base_url or api_key';
    } else {
      $url = rtrim($base, '/') . '/api/v1/system/status';
      [$code, $body, $err] = http_get($url, ['X-Api-Key: ' . $apiKey]);
      if ($code >= 200 && $code < 300) {
        $j = json_decode($body, true);
        if (is_array($j)) { $status = 'ok'; $msg = 'Prowlarr API OK'; }
        else { $msg = 'Prowlarr responded but JSON invalid'; }
      } else {
        $msg = 'Prowlarr HTTP ' . $code . ($err ? ' (' . $err . ')' : '');
      }
    }
  }

  if ($type === 'seedr') {
    // No fake endpoints. We sanity-check encrypted creds exist.
    $user = decrypt_secret((string)($row['username_enc'] ?? ''));
    $pass = decrypt_secret((string)($row['password_enc'] ?? ''));
    if ($user !== '' && $pass !== '') {
      $status = 'ok';
      $msg = 'Credentials stored (API login test not implemented yet)';
    } else {
      $msg = 'Missing username/password';
    }
  }

  if ($type === 'trakt') {
    // We sanity-check client_id in config + encrypted secret exists.
    $clientId = (string)($cfg['client_id'] ?? '');
    $secret   = decrypt_secret((string)($row['password_enc'] ?? ''));
    if ($clientId !== '' && $secret !== '') {
      $status = 'ok';
      $msg = 'Client ID/Secret stored (OAuth flow next)';
    } else {
      $msg = 'Missing client_id and/or client_secret';
    }
  }

  $upd = $pdo->prepare("
    UPDATE user_integrations
    SET last_test_status = ?, last_test_message = ?, last_test_at = NOW()
    WHERE user_id = ? AND integration_type = ?
  ");
  $upd->execute([$status, $msg, $uid, $type]);

  echo json_encode(['ok'=>true,'status'=>$status,'message'=>$msg], JSON_PRETTY_PRINT);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok'=>false,
    'where'=>'integrations_test.php',
    'error'=>$e->getMessage(),
  ], JSON_PRETTY_PRINT);
}
