<?php
declare(strict_types=1);

/**
 * Schema helpers
 *
 * WatchNexus has evolved through multiple migration iterations.
 * These helpers allow runtime feature detection so the app can
 * avoid hard 500s when a table/column is missing.
 */

function wnx_db_has_table(string $table): bool {
  static $cache = [];
  $key = strtolower($table);

  if (array_key_exists($key, $cache)) {
    return (bool)$cache[$key];
  }

  try {
    $pdo = db();
    $st = $pdo->prepare(
      "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?"
    );
    $st->execute([$table]);
    $cache[$key] = ((int)$st->fetchColumn() > 0);
  } catch (Throwable $e) {
    // If the information_schema query fails for any reason, fail closed.
    $cache[$key] = false;
  }

  return (bool)$cache[$key];
}

function wnx_db_has_column(string $table, string $column): bool {
  static $cache = [];
  $key = strtolower($table . '.' . $column);

  if (array_key_exists($key, $cache)) {
    return (bool)$cache[$key];
  }

  try {
    $pdo = db();
    $st = $pdo->prepare(
      "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
    );
    $st->execute([$table, $column]);
    $cache[$key] = ((int)$st->fetchColumn() > 0);
  } catch (Throwable $e) {
    $cache[$key] = false;
  }

  return (bool)$cache[$key];
}
