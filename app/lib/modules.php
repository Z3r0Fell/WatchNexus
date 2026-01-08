<?php
declare(strict_types=1);

/**
 * Module system (DB-backed)
 *
 * Rules:
 * - module_policy:
 *     disabled_globally = 1 => OFF for everyone
 *     force_enabled = 1     => ON for everyone
 *     enabled_by_default    => used if no user override
 * - user_module_overrides:
 *     enabled = 0/1 per user per module (ignored if force/disabled)
 */

function wnx_module_effective(string $moduleId, ?int $userId): bool {
  $pdo = db();

  // Global policy
  $st = $pdo->prepare("SELECT force_enabled, enabled_by_default, disabled_globally
                       FROM module_policy WHERE module_id = ? LIMIT 1");
  $st->execute([$moduleId]);
  $p = $st->fetch();

  // If not present, treat as enabled-by-default
  $force = $p ? (int)$p['force_enabled'] : 0;
  $def   = $p ? (int)$p['enabled_by_default'] : 1;
  $off   = $p ? (int)$p['disabled_globally'] : 0;

  if ($off === 1) return false;
  if ($force === 1) return true;

  // No user? use default
  if (!$userId) return (bool)$def;

  // User override
  $st = $pdo->prepare("SELECT enabled FROM user_module_overrides WHERE user_id = ? AND module_id = ? LIMIT 1");
  $st->execute([$userId, $moduleId]);
  $ov = $st->fetch();

  if ($ov) return ((int)$ov['enabled'] === 1);
  return (bool)$def;
}

/** Convenience for current session user */
function wnx_module_enabled(string $moduleId): bool {
  $u = current_user();
  $uid = $u ? (int)$u['id'] : null;
  return wnx_module_effective($moduleId, $uid);
}

/**
 * Snapshot for Settings (optional)
 * Returns forced state so UI can show "forced ON/OFF"
 */
function wnx_modules_policy_snapshot(int $userId): array {
  $pdo = db();

  $forced = [];
  $st = $pdo->query("SELECT module_id, force_enabled, disabled_globally FROM module_policy");
  while ($row = $st->fetch()) {
    $id = (string)$row['module_id'];
    $fe = (int)$row['force_enabled'];
    $dg = (int)$row['disabled_globally'];
    if ($dg === 1) $forced[$id] = 'off';
    else if ($fe === 1) $forced[$id] = 'on';
  }

  return ['forced' => $forced];
}

/**
 * Write user overrides (called from settings save)
 * Expects $modules = ['browse'=>true, 'trakt'=>false, ...]
 * Respects global force/disable rules.
 */
function wnx_save_user_module_overrides(int $userId, array $modules): void {
  $pdo = db();

  $pdo->beginTransaction();
  try {
    foreach ($modules as $id => $enabled) {
      $id = (string)$id;
      if ($id === '' || strlen($id) > 64) continue;

      // Skip core pages you never want user to disable
      if (in_array($id, ['calendar','settings'], true)) continue;

      // Read policy so we don't store pointless overrides
      $st = $pdo->prepare("SELECT force_enabled, disabled_globally FROM module_policy WHERE module_id = ? LIMIT 1");
      $st->execute([$id]);
      $p = $st->fetch();

      if ($p) {
        if ((int)$p['disabled_globally'] === 1) continue;
        if ((int)$p['force_enabled'] === 1) continue;
      }

      $val = $enabled ? 1 : 0;

      $st = $pdo->prepare("INSERT INTO user_module_overrides (user_id, module_id, enabled)
                           VALUES (?, ?, ?)
                           ON DUPLICATE KEY UPDATE enabled = VALUES(enabled)");
      $st->execute([$userId, $id, $val]);
    }

    $pdo->commit();
  } catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
  }
}
