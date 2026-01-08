<?php
declare(strict_types=1);

/**
 * Module system (DB-backed)
 *
 * Supports BOTH schemas:
 *  - New: module_policy (force_enabled, enabled_by_default, disabled_globally)
 *  - Legacy: global_module_policy (mode enum: default_on/default_off/forced_on/disabled_globally)
 */

function wnx_policy_from_legacy_mode(string $mode): array {
  $mode = (string)$mode;
  if ($mode === 'disabled_globally') {
    return ['force' => 0, 'def' => 0, 'off' => 1];
  }
  if ($mode === 'forced_on') {
    return ['force' => 1, 'def' => 1, 'off' => 0];
  }
  if ($mode === 'default_off') {
    return ['force' => 0, 'def' => 0, 'off' => 0];
  }
  // default_on (and unknown) => on by default
  return ['force' => 0, 'def' => 1, 'off' => 0];
}

function wnx_module_effective(string $moduleId, ?int $userId): bool {
  $pdo = db();

  // Default behavior if no policy tables exist
  $force = 0;
  $def   = 1;
  $off   = 0;

  // Prefer new schema
  if (function_exists('wnx_db_has_table') && wnx_db_has_table('module_policy')) {
    try {
      $st = $pdo->prepare(
        "SELECT force_enabled, enabled_by_default, disabled_globally FROM module_policy WHERE module_id = ? LIMIT 1"
      );
      $st->execute([$moduleId]);
      $p = $st->fetch();

      if ($p) {
        $force = (int)$p['force_enabled'];
        $def   = (int)$p['enabled_by_default'];
        $off   = (int)$p['disabled_globally'];
      }
    } catch (Throwable $e) {
      // fall through to legacy
    }
  }

  // Legacy schema fallback
  if (($force === 0 && $off === 0) && function_exists('wnx_db_has_table') && wnx_db_has_table('global_module_policy')) {
    try {
      $st = $pdo->prepare("SELECT mode FROM global_module_policy WHERE module_id = ? LIMIT 1");
      $st->execute([$moduleId]);
      $row = $st->fetch();
      if ($row && isset($row['mode'])) {
        $m = wnx_policy_from_legacy_mode((string)$row['mode']);
        $force = $m['force'];
        $def   = $m['def'];
        $off   = $m['off'];
      }
    } catch (Throwable $e) {
      // ignore
    }
  }

  if ($off === 1) return false;
  if ($force === 1) return true;

  // No user? use default
  if (!$userId) return (bool)$def;

  // User override table might not exist on early installs
  if (function_exists('wnx_db_has_table') && !wnx_db_has_table('user_module_overrides')) {
    return (bool)$def;
  }

  try {
    $st = $pdo->prepare("SELECT enabled FROM user_module_overrides WHERE user_id = ? AND module_id = ? LIMIT 1");
    $st->execute([$userId, $moduleId]);
    $ov = $st->fetch();
    if ($ov) return ((int)$ov['enabled'] === 1);
  } catch (Throwable $e) {
    // ignore and return default
  }

  return (bool)$def;
}

/** Convenience for current session user */
function wnx_module_enabled(string $moduleId): bool {
  $u = current_user();
  $uid = $u ? (int)$u['id'] : null;
  return wnx_module_effective($moduleId, $uid);
}

/**
 * Snapshot for Settings
 * Returns forced state so UI can show "forced ON/OFF"
 */
function wnx_modules_policy_snapshot(int $userId): array {
  $pdo = db();
  $forced = [];

  // Prefer new schema
  if (function_exists('wnx_db_has_table') && wnx_db_has_table('module_policy')) {
    try {
      $st = $pdo->query("SELECT module_id, force_enabled, disabled_globally FROM module_policy");
      while ($row = $st->fetch()) {
        $id = (string)$row['module_id'];
        $fe = (int)$row['force_enabled'];
        $dg = (int)$row['disabled_globally'];
        if ($dg === 1) $forced[$id] = 'off';
        else if ($fe === 1) $forced[$id] = 'on';
      }
      return ['forced' => $forced];
    } catch (Throwable $e) {
      // fall through
    }
  }

  // Legacy schema
  if (function_exists('wnx_db_has_table') && wnx_db_has_table('global_module_policy')) {
    try {
      $st = $pdo->query("SELECT module_id, mode FROM global_module_policy");
      while ($row = $st->fetch()) {
        $id = (string)$row['module_id'];
        $mode = (string)($row['mode'] ?? 'default_on');
        if ($mode === 'disabled_globally') $forced[$id] = 'off';
        else if ($mode === 'forced_on') $forced[$id] = 'on';
      }
    } catch (Throwable $e) {
      // ignore
    }
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

  // If override table missing, no-op (prevents 500 on partial installs)
  if (function_exists('wnx_db_has_table') && !wnx_db_has_table('user_module_overrides')) {
    return;
  }

  $pdo->beginTransaction();
  try {
    foreach ($modules as $id => $enabled) {
      $id = (string)$id;
      if ($id === '' || strlen($id) > 64) continue;

      // Skip core pages you never want user to disable
      if (in_array($id, ['calendar','settings'], true)) continue;

      $val = $enabled ? 1 : 0;

      // Respect policy when possible
      $blocked = false;

      if (function_exists('wnx_db_has_table') && wnx_db_has_table('module_policy')) {
        try {
          $st = $pdo->prepare("SELECT force_enabled, disabled_globally FROM module_policy WHERE module_id = ? LIMIT 1");
          $st->execute([$id]);
          $p = $st->fetch();
          if ($p) {
            if ((int)$p['disabled_globally'] === 1) $blocked = true;
            if ((int)$p['force_enabled'] === 1) $blocked = true;
          }
        } catch (Throwable $e) {}
      } elseif (function_exists('wnx_db_has_table') && wnx_db_has_table('global_module_policy')) {
        try {
          $st = $pdo->prepare("SELECT mode FROM global_module_policy WHERE module_id = ? LIMIT 1");
          $st->execute([$id]);
          $p = $st->fetch();
          if ($p) {
            $mode = (string)($p['mode'] ?? 'default_on');
            if ($mode === 'disabled_globally' || $mode === 'forced_on') $blocked = true;
          }
        } catch (Throwable $e) {}
      }

      if ($blocked) continue;

      $st = $pdo->prepare(
        "INSERT INTO user_module_overrides (user_id, module_id, enabled)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE enabled = VALUES(enabled)"
      );
      $st->execute([$userId, $id, $val]);
    }

    $pdo->commit();
  } catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
  }
}
