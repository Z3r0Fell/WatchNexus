<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

$u = current_user();
if (!$u) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'error'=>'Login required']);
  exit;
}

if (!has_role('admin')) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'error'=>'Admin only']);
  exit;
}

$pdo = db();

try {
  // Query the module_policy table (aligned with modules.php expectations)
  $st = $pdo->query("
    SELECT 
      module_id, 
      force_enabled, 
      enabled_by_default, 
      disabled_globally 
    FROM module_policy
  ");
  
  $policies = [];
  while ($r = $st->fetch()) {
    $id = (string)$r['module_id'];
    $forced = 'none';
    
    // Convert to simple forced state for UI
    if ((int)$r['disabled_globally'] === 1) {
      $forced = 'off';
    } else if ((int)$r['force_enabled'] === 1) {
      $forced = 'on';
    }
    
    $policies[$id] = [
      'forced' => $forced,
      'enabled_by_default' => ((int)$r['enabled_by_default'] === 1),
    ];
  }

  echo json_encode(['ok'=>true,'policies'=>$policies], JSON_PRETTY_PRINT);
  
} catch (Throwable $e) {
  // Table might not exist yet, return empty
  echo json_encode([
    'ok'=>true,
    'policies'=>[],
    'note'=>'module_policy table not found: ' . $e->getMessage()
  ], JSON_PRETTY_PRINT);
}
