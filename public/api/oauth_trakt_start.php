<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

if (!current_user()) {
  header('Location: /?page=login');
  exit;
}

$pdo = db();

// Get Trakt client ID from system config
$stmt = $pdo->prepare("SELECT config_value_plain FROM system_config WHERE config_key = 'trakt_client_id'");
$stmt->execute();
$clientId = $stmt->fetchColumn();

if (!$clientId) {
  die('Trakt is not configured. Admin must add Client ID in Admin → Data Sources.');
}

// Generate state token for CSRF protection
$state = bin2hex(random_bytes(16));
$_SESSION['trakt_oauth_state'] = $state;
$_SESSION['trakt_oauth_time'] = time();

// Build authorization URL
$redirectUri = ($_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/api/oauth_trakt_callback.php';

$params = http_build_query([
  'response_type' => 'code',
  'client_id' => $clientId,
  'redirect_uri' => $redirectUri,
  'state' => $state
]);

$authUrl = 'https://api.trakt.tv/oauth/authorize?' . $params;

// Redirect to Trakt
header('Location: ' . $authUrl);
exit;
