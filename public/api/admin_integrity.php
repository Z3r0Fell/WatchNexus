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
  $issues = [];
  $stats = [];
  
  // Check for orphaned events (events without shows)
  $orphanedEvents = $pdo->query("
    SELECT COUNT(*) FROM events e 
    LEFT JOIN shows s ON s.id = e.show_id 
    WHERE s.id IS NULL
  ")->fetchColumn();
  
  if ($orphanedEvents > 0) {
    $issues[] = "Found $orphanedEvents orphaned events (no matching show)";
  }
  $stats['orphaned_events'] = $orphanedEvents;
  
  // Check for orphaned tracked shows
  $orphanedTracked = $pdo->query("
    SELECT COUNT(*) FROM user_tracked_shows uts
    LEFT JOIN shows s ON s.id = uts.show_id
    WHERE s.id IS NULL
  ")->fetchColumn();
  
  if ($orphanedTracked > 0) {
    $issues[] = "Found $orphanedTracked orphaned tracked shows (no matching show)";
  }
  $stats['orphaned_tracked'] = $orphanedTracked;
  
  // Check for orphaned user_tracked_shows (deleted users)
  $orphanedUserTracked = $pdo->query("
    SELECT COUNT(*) FROM user_tracked_shows uts
    LEFT JOIN users u ON u.id = uts.user_id
    WHERE u.id IS NULL
  ")->fetchColumn();
  
  if ($orphanedUserTracked > 0) {
    $issues[] = "Found $orphanedUserTracked orphaned tracked shows (deleted users)";
  }
  $stats['orphaned_user_tracked'] = $orphanedUserTracked;
  
  // Check for duplicate external IDs
  $duplicateExternalIds = $pdo->query("
    SELECT provider, external_id, COUNT(*) as cnt
    FROM show_external_ids
    GROUP BY provider, external_id
    HAVING cnt > 1
  ")->fetchAll();
  
  if (count($duplicateExternalIds) > 0) {
    $issues[] = "Found " . count($duplicateExternalIds) . " duplicate external ID mappings";
  }
  $stats['duplicate_external_ids'] = count($duplicateExternalIds);
  
  // Check for shows without external IDs
  $showsWithoutIds = $pdo->query("
    SELECT COUNT(*) FROM shows s
    LEFT JOIN show_external_ids sei ON sei.show_id = s.id
    WHERE sei.id IS NULL
  ")->fetchColumn();
  
  if ($showsWithoutIds > 0) {
    $issues[] = "Found $showsWithoutIds shows without external IDs (may have matching issues)";
  }
  $stats['shows_without_external_ids'] = $showsWithoutIds;
  
  // Check for events in the past (cleanup opportunity)
  $oldEvents = $pdo->query("
    SELECT COUNT(*) FROM events
    WHERE start_utc < DATE_SUB(NOW(), INTERVAL 90 DAYS)
  ")->fetchColumn();
  
  if ($oldEvents > 1000) {
    $issues[] = "Found $oldEvents events older than 90 days (consider cleanup)";
  }
  $stats['old_events'] = $oldEvents;
  
  // Table sizes
  $stats['total_shows'] = $pdo->query("SELECT COUNT(*) FROM shows")->fetchColumn();
  $stats['total_events'] = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
  $stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
  $stats['active_users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
  
  echo json_encode([
    'ok' => true,
    'issues' => $issues,
    'stats' => $stats,
    'health' => count($issues) === 0 ? 'good' : (count($issues) <= 2 ? 'fair' : 'needs_attention')
  ], JSON_PRETTY_PRINT);
  
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
