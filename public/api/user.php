<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
header('Content-Type: application/json');

require_role_api('user');
demo_user_state_init();

$action = $_POST['action'] ?? '';

if ($action === 'track' || $action === 'untrack') {
  $id = intval($_POST['id'] ?? 0);
  if ($id <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Missing id']); exit; }

  $tracked = $_SESSION['demo']['tracked'] ?? [];
  $tracked = array_values(array_unique(array_map('intval', $tracked)));

  if ($action === 'track') {
    if (!in_array($id, $tracked, true)) $tracked[] = $id;
  } else {
    $tracked = array_values(array_filter($tracked, fn($x) => $x !== $id));
  }
  $_SESSION['demo']['tracked'] = $tracked;
  echo json_encode(['ok'=>true,'tracked'=>$tracked]);
  exit;
}

if ($action === 'integrations_load') {
  $ints = $_SESSION['demo']['integrations'] ?? [];
  // Decrypt what we can for demo UI
  $safe = $ints;
  foreach (['seedrUser','seedrPass','jackettKey','prowlarrKey','traktClientSecret'] as $k) {
    if (isset($safe[$k]) && $safe[$k] !== '') {
      try { $safe[$k] = decrypt_secret((string)$safe[$k]); } catch (Throwable $e) { $safe[$k] = ''; }
    }
  }
  echo json_encode(['ok'=>true,'integrations'=>$safe]);
  exit;
}

if ($action === 'integrations_save') {
  $data = [
    'traktClientId' => trim((string)($_POST['traktClientId'] ?? '')),
    'traktClientSecret' => encrypt_secret(trim((string)($_POST['traktClientSecret'] ?? ''))),
    'seedrVariant' => trim((string)($_POST['seedrVariant'] ?? 'auto')),
    'seedrUser' => encrypt_secret(trim((string)($_POST['seedrUser'] ?? ''))),
    'seedrPass' => encrypt_secret(trim((string)($_POST['seedrPass'] ?? ''))),
    'jackettUrl' => trim((string)($_POST['jackettUrl'] ?? '')),
    'jackettKey' => encrypt_secret(trim((string)($_POST['jackettKey'] ?? ''))),
    'prowlarrUrl' => trim((string)($_POST['prowlarrUrl'] ?? '')),
    'prowlarrKey' => encrypt_secret(trim((string)($_POST['prowlarrKey'] ?? ''))),
  ];
  $_SESSION['demo']['integrations'] = $data;
  echo json_encode(['ok'=>true]);
  exit;
}

http_response_code(400);
echo json_encode(['ok'=>false,'error'=>'Unsupported action']);
