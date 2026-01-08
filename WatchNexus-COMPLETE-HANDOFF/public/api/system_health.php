<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

if (!has_role('admin')) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'error' => 'Admin only']);
  exit;
}

try {
  $diskTotal = disk_total_space('/');
  $diskFree = disk_free_space('/');
  
  echo json_encode([
    'ok' => true,
    'disk_total' => $diskTotal,
    'disk_free' => $diskFree,
    'disk_free_gb' => round($diskFree / 1024 / 1024 / 1024, 2),
    'disk_total_gb' => round($diskTotal / 1024 / 1024 / 1024, 2),
    'memory_usage' => memory_get_usage(true),
    'memory_peak' => memory_get_peak_usage(true),
    'php_version' => PHP_VERSION,
  ], JSON_PRETTY_PRINT);
  
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
