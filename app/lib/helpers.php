<?php
declare(strict_types=1);

/**
 * Helper functions for API endpoints
 */

function require_role_api(string $role): void {
  if (!has_role($role)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Insufficient permissions']);
    exit;
  }
}

function wnx_module_user_can_toggle(string $moduleId): bool {
  // Core modules can't be toggled
  $core = ['calendar', 'settings'];
  if (in_array($moduleId, $core, true)) {
    return false;
  }
  
  // Check if globally disabled or forced
  $pdo = db();
  $st = $pdo->prepare("SELECT force_enabled, disabled_globally FROM module_policy WHERE module_id = ? LIMIT 1");
  $st->execute([$moduleId]);
  $p = $st->fetch();
  
  if ($p) {
    if ((int)$p['disabled_globally'] === 1) return false;
    if ((int)$p['force_enabled'] === 1) return false;
  }
  
  return true;
}

function wnx_user_override_set(string $moduleId, bool $enabled): void {
  $u = current_user();
  if (!$u) return;
  
  $uid = (int)$u['id'];
  $pdo = db();
  
  $val = $enabled ? 1 : 0;
  $st = $pdo->prepare("
    INSERT INTO user_module_overrides (user_id, module_id, enabled)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE enabled = VALUES(enabled)
  ");
  $st->execute([$uid, $moduleId, $val]);
}

function wnx_policy_load(): array {
  $pdo = db();
  try {
    $st = $pdo->query("SELECT module_id, force_enabled, enabled_by_default, disabled_globally FROM module_policy");
    $modules = [];
    while ($row = $st->fetch()) {
      $id = (string)$row['module_id'];
      $fe = (int)$row['force_enabled'];
      $def = (int)$row['enabled_by_default'];
      $dg = (int)$row['disabled_globally'];
      
      if ($dg === 1) {
        $mode = 'disabled_globally';
      } elseif ($fe === 1) {
        $mode = 'forced_on';
      } elseif ($def === 1) {
        $mode = 'default_on';
      } else {
        $mode = 'default_off';
      }
      
      $modules[$id] = ['mode' => $mode];
    }
    return ['modules' => $modules];
  } catch (Throwable $e) {
    return ['modules' => []];
  }
}

function wnx_policy_save(array $policy): bool {
  if (!isset($policy['modules']) || !is_array($policy['modules'])) {
    return false;
  }
  
  $pdo = db();
  
  try {
    $pdo->beginTransaction();
    
    foreach ($policy['modules'] as $id => $data) {
      $mode = $data['mode'] ?? 'default_on';
      
      $fe = ($mode === 'forced_on') ? 1 : 0;
      $def = ($mode === 'default_on' || $mode === 'forced_on') ? 1 : 0;
      $dg = ($mode === 'disabled_globally') ? 1 : 0;
      
      $st = $pdo->prepare("
        INSERT INTO module_policy (module_id, force_enabled, enabled_by_default, disabled_globally)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          force_enabled = VALUES(force_enabled),
          enabled_by_default = VALUES(enabled_by_default),
          disabled_globally = VALUES(disabled_globally)
      ");
      $st->execute([$id, $fe, $def, $dg]);
    }
    
    $pdo->commit();
    return true;
  } catch (Throwable $e) {
    $pdo->rollBack();
    return false;
  }
}

function logout_user(): void {
  auth_logout();
}

function require_login_html(): void {
  $u = current_user();
  if (!$u) {
    echo '<div class="banner warn"><div class="badge">Login required</div><div><p>Please <a href="/?page=login">login</a> to access this page.</p></div></div>';
    exit;
  }
}
