<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

if (!has_role('admin')) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'error' => 'Admin only']);
  exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$clientId = trim((string)($data['client_id'] ?? ''));
$clientSecret = trim((string)($data['client_secret'] ?? ''));

if ($clientId === '' || $clientSecret === '') {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Client ID and Secret are required']);
  exit;
}

$pdo = db();

try {
  // Encrypt the client secret
  $clientSecretEnc = encrypt_secret($clientSecret);
  
  // Store in system_config table
  $stmt = $pdo->prepare("
    INSERT INTO system_config (config_key, config_value_plain, config_value_enc) 
    VALUES ('trakt_client_id', ?, NULL),
           ('trakt_client_secret', NULL, ?)
    ON DUPLICATE KEY UPDATE 
      config_value_plain = VALUES(config_value_plain),
      config_value_enc = VALUES(config_value_enc)
  ");
  
  $stmt->execute([$clientId, $clientSecretEnc]);
  
  echo json_encode([
    'ok' => true,
    'message' => 'Trakt configuration saved successfully'
  ], JSON_PRETTY_PRINT);
  
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
