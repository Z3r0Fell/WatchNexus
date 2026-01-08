<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

if (!current_user()) {
  header('Location: /?page=login');
  exit;
}

$code = $_GET['code'] ?? null;
$state = $_GET['state'] ?? null;

// Verify state token
if (!$state || !isset($_SESSION['trakt_oauth_state']) || $state !== $_SESSION['trakt_oauth_state']) {
  die('Invalid state token. Possible CSRF attack.');
}

// Check token age (5 minutes max)
if (!isset($_SESSION['trakt_oauth_time']) || (time() - $_SESSION['trakt_oauth_time']) > 300) {
  die('OAuth session expired. Please try again.');
}

unset($_SESSION['trakt_oauth_state'], $_SESSION['trakt_oauth_time']);

if (!$code) {
  die('Authorization failed. No code received.');
}

$pdo = db();

// Get Trakt credentials from system config
$stmt = $pdo->prepare("
  SELECT config_key, config_value_plain, config_value_enc 
  FROM system_config 
  WHERE config_key IN ('trakt_client_id', 'trakt_client_secret')
");
$stmt->execute();
$config = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
  if ($row['config_key'] === 'trakt_client_id') {
    $config['client_id'] = $row['config_value_plain'];
  } else {
    $config['client_secret'] = decrypt_secret($row['config_value_enc']);
  }
}

if (!$config['client_id'] || !$config['client_secret']) {
  die('Trakt is not fully configured. Admin must configure both Client ID and Secret.');
}

// Exchange code for access token
$redirectUri = ($_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/api/oauth_trakt_callback.php';

$ch = curl_init('https://api.trakt.tv/oauth/token');
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_HTTPHEADER => [
    'Content-Type: application/json'
  ],
  CURLOPT_POSTFIELDS => json_encode([
    'code' => $code,
    'client_id' => $config['client_id'],
    'client_secret' => $config['client_secret'],
    'redirect_uri' => $redirectUri,
    'grant_type' => 'authorization_code'
  ])
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
  die('Token exchange failed. HTTP ' . $httpCode . ': ' . $response);
}

$tokenData = json_decode($response, true);

if (!$tokenData || !isset($tokenData['access_token'])) {
  die('Invalid token response: ' . $response);
}

// Store tokens in user_integrations table
$accessToken = $tokenData['access_token'];
$refreshToken = $tokenData['refresh_token'] ?? null;
$expiresIn = $tokenData['expires_in'] ?? 7776000; // Default 90 days

$credentials = [
  'access_token' => $accessToken,
  'refresh_token' => $refreshToken,
  'expires_at' => time() + $expiresIn
];

$credentialsEnc = encrypt_secret(json_encode($credentials));

$userId = current_user()['id'];

// Insert or update Trakt integration
$stmt = $pdo->prepare("
  INSERT INTO user_integrations (user_id, integration_type, base_url, api_variant, credentials_encrypted, enabled)
  VALUES (?, 'trakt', 'https://api.trakt.tv', 'oauth', ?, 1)
  ON DUPLICATE KEY UPDATE
    credentials_encrypted = VALUES(credentials_encrypted),
    enabled = 1,
    updated_at = NOW()
");

$stmt->execute([$userId, $credentialsEnc]);

// Redirect to settings with success message
header('Location: /?page=settings&trakt_connected=1');
exit;
