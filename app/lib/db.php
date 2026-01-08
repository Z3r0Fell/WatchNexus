<?php
declare(strict_types=1);

function db(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;

  global $WNX_CONFIG;
  if (!isset($WNX_CONFIG['db'])) {
    throw new RuntimeException('DB config missing');
  }

  $c = $WNX_CONFIG['db'];
  $dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=%s',
    $c['host'],
    $c['name'],
    $c['charset'] ?? 'utf8mb4'
  );

  $pdo = new PDO($dsn, $c['user'], $c['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ]);

  // Ensure consistent SQL mode / timezone behavior
  $pdo->exec("SET time_zone = '+00:00'");

  return $pdo;
}
