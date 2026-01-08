<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = db();

try {
  // Get all shows with basic info
  $stmt = $pdo->query("
    SELECT 
      id, 
      title, 
      description, 
      poster_url, 
      status, 
      premiered
    FROM shows
    ORDER BY title ASC
  ");
  
  $shows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  echo json_encode([
    'ok' => true,
    'shows' => $shows,
    'count' => count($shows)
  ], JSON_PRETTY_PRINT);
  
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'error' => $e->getMessage()
  ]);
}
