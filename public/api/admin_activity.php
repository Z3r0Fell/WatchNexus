<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

if (!has_role('admin')) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'error' => 'Admin only']);
  exit;
}

$pdo = db();

try {
  $activity = [];
  
  // New users last 7 days
  $newUsers = $pdo->query("
    SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
  ")->fetchColumn();
  $activity[] = ['label' => 'New Users', 'count' => $newUsers];
  
  // Active users (logged in last 7 days) - approximation based on session activity
  // Since we don't have login tracking yet, we'll use created_at as proxy
  $activeUsers = $pdo->query("
    SELECT COUNT(DISTINCT user_id) FROM user_tracked_shows 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
  ")->fetchColumn();
  $activity[] = ['label' => 'Active Users', 'count' => $activeUsers];
  
  // New tracked shows
  $newTracked = $pdo->query("
    SELECT COUNT(*) FROM user_tracked_shows 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
  ")->fetchColumn();
  $activity[] = ['label' => 'New Tracked Shows', 'count' => $newTracked];
  
  // Active integrations
  $activeIntegrations = $pdo->query("
    SELECT COUNT(*) FROM user_integrations WHERE enabled = 1
  ")->fetchColumn();
  $activity[] = ['label' => 'Active Integrations', 'count' => $activeIntegrations];
  
  echo json_encode([
    'ok' => true,
    'activity' => $activity
  ], JSON_PRETTY_PRINT);
  
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
