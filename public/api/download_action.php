<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

$u = current_user();
if (!$u) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'error' => 'Login required']);
  exit;
}

$uid = (int)$u['id'];

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$query = $data['query'] ?? '';

if (!in_array($action, ['seedr', 'jackett', 'prowlarr'], true) || $query === '') {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid request']);
  exit;
}

$pdo = db();

try {
  // Get user's integration config
  $st = $pdo->prepare("
    SELECT base_url, api_variant, api_key_enc, username_enc, password_enc, access_token_enc, enabled
    FROM user_integrations
    WHERE user_id = ? AND integration_type = ?
    LIMIT 1
  ");
  $st->execute([$uid, $action]);
  $row = $st->fetch();
  
  if (!$row || (int)$row['enabled'] !== 1) {
    http_response_code(400);
    echo json_encode([
      'ok' => false, 
      'error' => ucfirst($action) . ' is not configured or disabled. Enable it in Settings → Integrations.'
    ]);
    exit;
  }
  
  $baseUrl = trim((string)$row['base_url']);
  
  if ($action === 'seedr') {
    // Seedr: User would need to first search via Jackett/Prowlarr, then send magnet to Seedr
    echo json_encode([
      'ok' => false, 
      'error' => 'Direct Seedr add not implemented. Search with Jackett/Prowlarr first, then copy magnet link to Seedr manually.'
    ]);
    exit;
  }
  
  if ($action === 'jackett') {
    // Search Jackett indexers
    $apiKey = decrypt_secret((string)$row['api_key_enc']);
    
    if ($baseUrl === '' || $apiKey === '') {
      http_response_code(400);
      echo json_encode(['ok' => false, 'error' => 'Jackett not fully configured. Add base URL and API key in Settings.']);
      exit;
    }
    
    $url = rtrim($baseUrl, '/') . '/api/v2.0/indexers/all/results?apikey=' . urlencode($apiKey) . '&Query=' . urlencode($query);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($code !== 200) {
      http_response_code(502);
      echo json_encode(['ok' => false, 'error' => 'Jackett search failed (HTTP ' . $code . ')' . ($err ? ': ' . $err : '')]);
      exit;
    }
    
    $results = json_decode($resp, true);
    if (!isset($results['Results']) || !is_array($results['Results'])) {
      http_response_code(502);
      echo json_encode(['ok' => false, 'error' => 'Invalid response from Jackett']);
      exit;
    }
    
    // Return top 15 results, sorted by seeders
    $torrents = $results['Results'];
    usort($torrents, function($a, $b) {
      return ((int)($b['Seeders'] ?? 0)) - ((int)($a['Seeders'] ?? 0));
    });
    $torrents = array_slice($torrents, 0, 15);
    
    $simplified = [];
    foreach ($torrents as $t) {
      $simplified[] = [
        'title' => $t['Title'] ?? 'Unknown',
        'size' => $t['Size'] ?? 0,
        'seeders' => $t['Seeders'] ?? 0,
        'peers' => $t['Peers'] ?? 0,
        'magnet' => $t['MagnetUri'] ?? null,
        'download' => $t['Link'] ?? null,
        'indexer' => $t['Tracker'] ?? 'Unknown',
      ];
    }
    
    echo json_encode(['ok' => true, 'results' => $simplified]);
    exit;
  }
  
  if ($action === 'prowlarr') {
    // Search Prowlarr
    $apiKey = decrypt_secret((string)$row['api_key_enc']);
    
    if ($baseUrl === '' || $apiKey === '') {
      http_response_code(400);
      echo json_encode(['ok' => false, 'error' => 'Prowlarr not fully configured. Add base URL and API key in Settings.']);
      exit;
    }
    
    // Prowlarr search: GET /api/v1/search?query=...&type=search
    $url = rtrim($baseUrl, '/') . '/api/v1/search?query=' . urlencode($query) . '&type=search';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Api-Key: ' . $apiKey]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($code !== 200) {
      http_response_code(502);
      echo json_encode(['ok' => false, 'error' => 'Prowlarr search failed (HTTP ' . $code . ')' . ($err ? ': ' . $err : '')]);
      exit;
    }
    
    $results = json_decode($resp, true);
    if (!is_array($results)) {
      http_response_code(502);
      echo json_encode(['ok' => false, 'error' => 'Invalid response from Prowlarr']);
      exit;
    }
    
    // Sort by seeders and take top 15
    usort($results, function($a, $b) {
      return ((int)($b['seeders'] ?? 0)) - ((int)($a['seeders'] ?? 0));
    });
    $torrents = array_slice($results, 0, 15);
    
    $simplified = [];
    foreach ($torrents as $t) {
      $simplified[] = [
        'title' => $t['title'] ?? 'Unknown',
        'size' => $t['size'] ?? 0,
        'seeders' => $t['seeders'] ?? 0,
        'peers' => $t['leechers'] ?? 0,
        'magnet' => $t['magnetUrl'] ?? null,
        'download' => $t['downloadUrl'] ?? null,
        'indexer' => $t['indexer'] ?? 'Unknown',
      ];
    }
    
    echo json_encode(['ok' => true, 'results' => $simplified]);
    exit;
  }
  
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
