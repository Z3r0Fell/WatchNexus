<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

$user = current_user();
if (!$user) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'error' => 'Login required']);
  exit;
}

$pdo = db();

try {
  // Get user's Trakt integration
  $stmt = $pdo->prepare("
    SELECT credentials_encrypted
    FROM user_integrations
    WHERE user_id = ? AND integration_type = 'trakt' AND enabled = 1
  ");
  $stmt->execute([$user['id']]);
  $integration = $stmt->fetch(PDO::FETCH_ASSOC);
  
  if (!$integration) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Trakt not connected']);
    exit;
  }
  
  // Decrypt credentials
  $credentials = json_decode(decrypt_secret($integration['credentials_encrypted']), true);
  $accessToken = $credentials['access_token'] ?? null;
  
  if (!$accessToken) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid Trakt token']);
    exit;
  }
  
  // Get Trakt client ID for API calls
  $stmt = $pdo->prepare("SELECT config_value_plain FROM system_config WHERE config_key = 'trakt_client_id'");
  $stmt->execute();
  $clientId = $stmt->fetchColumn();
  
  if (!$clientId) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Trakt not configured by admin']);
    exit;
  }
  
  // Fetch user's watched shows from Trakt
  $ch = curl_init('https://api.trakt.tv/sync/watched/shows');
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
      'Content-Type: application/json',
      'Authorization: Bearer ' . $accessToken,
      'trakt-api-version: 2',
      'trakt-api-key: ' . $clientId
    ],
    CURLOPT_TIMEOUT => 15
  ]);
  
  $response = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  
  if ($httpCode !== 200) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Trakt API error: HTTP ' . $httpCode]);
    exit;
  }
  
  $watchedShows = json_decode($response, true);
  
  if (!is_array($watchedShows)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Invalid Trakt response']);
    exit;
  }
  
  // Match and track shows
  $matched = 0;
  $unmatched = [];
  
  $pdo->beginTransaction();
  
  foreach ($watchedShows as $item) {
    $show = $item['show'] ?? [];
    $title = $show['title'] ?? null;
    $traktId = $show['ids']['trakt'] ?? null;
    $tvdbId = $show['ids']['tvdb'] ?? null;
    
    if (!$title) continue;
    
    // Try to find show in database by external IDs
    $localShowId = null;
    
    if ($tvdbId) {
      $stmt = $pdo->prepare("
        SELECT show_id FROM show_external_ids 
        WHERE provider = 'tvdb' AND external_id = ?
      ");
      $stmt->execute([(string)$tvdbId]);
      $localShowId = $stmt->fetchColumn();
    }
    
    // Fallback: exact title match
    if (!$localShowId) {
      $stmt = $pdo->prepare("SELECT id FROM shows WHERE LOWER(title) = LOWER(?)");
      $stmt->execute([$title]);
      $localShowId = $stmt->fetchColumn();
    }
    
    if ($localShowId) {
      // Track the show
      $stmt = $pdo->prepare("
        INSERT IGNORE INTO user_tracked_shows (user_id, show_id)
        VALUES (?, ?)
      ");
      $stmt->execute([$user['id'], $localShowId]);
      $matched++;
    } else {
      $unmatched[] = $title;
    }
  }
  
  $pdo->commit();
  
  echo json_encode([
    'ok' => true,
    'total_watched' => count($watchedShows),
    'matched' => $matched,
    'unmatched' => $unmatched
  ], JSON_PRETTY_PRINT);
  
} catch (Throwable $e) {
  if (isset($pdo) && $pdo->inTransaction()) {
    $pdo->rollBack();
  }
  
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'error' => $e->getMessage()
  ]);
}
