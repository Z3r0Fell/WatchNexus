<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

if (!has_role('mod')) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'error' => 'Mod or admin access required']);
  exit;
}

// Check if there's an import progress file in /tmp
$progressFiles = glob('/tmp/wnx_import_progress_*');

if (empty($progressFiles)) {
  echo json_encode([
    'ok' => true,
    'in_progress' => false
  ], JSON_PRETTY_PRINT);
  exit;
}

// Get the most recent progress file
$progressFile = $progressFiles[0];
$data = @file_get_contents($progressFile);

if (!$data) {
  echo json_encode([
    'ok' => true,
    'in_progress' => false
  ], JSON_PRETTY_PRINT);
  exit;
}

$progress = json_decode($data, true);

// Check if import finished (file older than 10 seconds means stuck or done)
$fileAge = time() - filemtime($progressFile);
if ($fileAge > 10 && ($progress['progress'] ?? 0) >= 100) {
  @unlink($progressFile);
  echo json_encode([
    'ok' => true,
    'in_progress' => false
  ], JSON_PRETTY_PRINT);
  exit;
}

echo json_encode([
  'ok' => true,
  'in_progress' => true,
  'type' => $progress['type'] ?? 'Unknown',
  'status' => $progress['status'] ?? 'Processing...',
  'progress' => $progress['progress'] ?? 0,
  'current' => $progress['current'] ?? 0,
  'total' => $progress['total'] ?? 0
], JSON_PRETTY_PRINT);
