<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

try {
  $pdo = db();
  $v = $pdo->query("SELECT VERSION() AS v")->fetch();
  $t = $pdo->query("SELECT NOW() AS n")->fetch();

  echo "DB CONNECT OK\n";
  echo "MySQL: " . ($v['v'] ?? 'unknown') . "\n";
  echo "Server time: " . ($t['n'] ?? 'unknown') . "\n";
} catch (Throwable $e) {
  http_response_code(500);
  echo "DB CONNECT FAIL\n";
  echo $e->getMessage() . "\n";
}
