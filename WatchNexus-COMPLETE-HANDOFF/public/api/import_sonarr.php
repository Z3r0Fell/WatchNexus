<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

// Mod/Admin only
if (!has_role('mod')) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'error' => 'Moderator or admin access required']);
  exit;
}

$u = current_user();
$uid = (int)$u['id'];

// Check if file was uploaded
if (!isset($_FILES['sonarr_backup']) || $_FILES['sonarr_backup']['error'] !== UPLOAD_ERR_OK) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'No file uploaded or upload failed']);
  exit;
}

$uploadedFile = $_FILES['sonarr_backup']['tmp_name'];
$fileName = $_FILES['sonarr_backup']['name'];

// Create progress tracking file
$progressFile = '/tmp/wnx_import_progress_' . uniqid();

function updateProgress($file, $status, $current, $total) {
  $progress = [
    'type' => 'Sonarr',
    'status' => $status,
    'current' => $current,
    'total' => $total,
    'progress' => $total > 0 ? round(($current / $total) * 100) : 0,
    'updated_at' => time()
  ];
  @file_put_contents($file, json_encode($progress));
}

// Verify it's a ZIP
if (!str_ends_with(strtolower($fileName), '.zip')) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'File must be a ZIP archive']);
  exit;
}

$pdo = db();
$tempDir = sys_get_temp_dir() . '/sonarr_import_' . uniqid();

try {
  updateProgress($progressFile, 'Extracting backup...', 0, 100);
  
  // Create temp directory
  if (!mkdir($tempDir, 0700, true)) {
    throw new Exception('Failed to create temporary directory');
  }
  
  // Extract ZIP
  $zip = new ZipArchive();
  if ($zip->open($uploadedFile) !== true) {
    throw new Exception('Failed to open ZIP file');
  }
  
  $zip->extractTo($tempDir);
  $zip->close();
  
  updateProgress($progressFile, 'Finding database...', 10, 100);
  
  // Find sonarr.db file
  $dbPath = null;
  $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tempDir));
  foreach ($iterator as $file) {
    if ($file->isFile() && $file->getFilename() === 'sonarr.db') {
      $dbPath = $file->getPathname();
      break;
    }
  }
  
  if (!$dbPath) {
    throw new Exception('sonarr.db not found in backup archive');
  }
  
  // Open Sonarr SQLite database
  $sonarrDb = new PDO('sqlite:' . $dbPath);
  $sonarrDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  
  updateProgress($progressFile, 'Reading Sonarr database...', 20, 100);
  
  // Query monitored shows from Series table
  $stmt = $sonarrDb->query("
    SELECT TvdbId, Title, Monitored
    FROM Series
    WHERE Monitored = 1
  ");
  
  $monitoredShows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $totalMonitored = count($monitoredShows);
  
  if ($totalMonitored === 0) {
    @unlink($progressFile);
    echo json_encode([
      'ok' => true,
      'tracked' => 0,
      'matched' => 0,
      'total_monitored' => 0,
      'unmatched' => [],
      'message' => 'No monitored shows found in Sonarr backup'
    ]);
    exit;
  }
  
  updateProgress($progressFile, "Matching $totalMonitored shows...", 30, 100);
  
  $pdo->beginTransaction();
  
  $tracked = 0;
  $matched = 0;
  $unmatched = [];
  
  foreach ($monitoredShows as $index => $show) {
    $tvdbId = (string)$show['TvdbId'];
    $title = (string)$show['Title'];
    
    // Update progress every 10 shows
    if ($index % 10 === 0) {
      $percent = 30 + (int)(($index / $totalMonitored) * 60);
      updateProgress($progressFile, "Matching shows... ($index/$totalMonitored)", $percent, 100);
    }
    
    // Try to match by TVDb ID first
    $st = $pdo->prepare("
      SELECT s.id 
      FROM shows s
      JOIN show_external_ids sei ON sei.show_id = s.id
      WHERE sei.provider = 'tvdb' AND sei.external_id = ?
      LIMIT 1
    ");
    $st->execute([$tvdbId]);
    $showId = $st->fetchColumn();
    
    // If no match by TVDb, try exact title match
    if (!$showId) {
      $st = $pdo->prepare("
        SELECT id FROM shows WHERE LOWER(title) = LOWER(?) LIMIT 1
      ");
      $st->execute([$title]);
      $showId = $st->fetchColumn();
    }
    
    if (!$showId) {
      $unmatched[] = $title;
      continue;
    }
    
    $matched++;
    
    // Check if already tracked
    $st = $pdo->prepare("
      SELECT 1 FROM user_tracked_shows 
      WHERE user_id = ? AND show_id = ?
    ");
    $st->execute([$uid, $showId]);
    
    if ($st->fetchColumn()) {
      // Already tracked, skip
      continue;
    }
    
    // Track the show
    $ins = $pdo->prepare("
      INSERT INTO user_tracked_shows (user_id, show_id) VALUES (?, ?)
    ");
    $ins->execute([$uid, $showId]);
    $tracked++;
  }
  
  $pdo->commit();
  
  updateProgress($progressFile, 'Completed!', 100, 100);
  sleep(2);
  @unlink($progressFile);
  
  echo json_encode([
    'ok' => true,
    'tracked' => $tracked,
    'matched' => $matched,
    'total_monitored' => $totalMonitored,
    'unmatched' => $unmatched
  ], JSON_PRETTY_PRINT);
  
} catch (Throwable $e) {
  if (isset($pdo) && $pdo->inTransaction()) {
    $pdo->rollBack();
  }
  
  @unlink($progressFile);
  
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'error' => $e->getMessage(),
    'file' => $e->getFile(),
    'line' => $e->getLine()
  ]);
} finally {
  // Clean up temp directory
  if (isset($tempDir) && is_dir($tempDir)) {
    $files = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
      RecursiveIteratorIterator::CHILD_FIRST
    );
    
    foreach ($files as $file) {
      if ($file->isDir()) {
        @rmdir($file->getRealPath());
      } else {
        @unlink($file->getRealPath());
      }
    }
    
    @rmdir($tempDir);
  }
}
