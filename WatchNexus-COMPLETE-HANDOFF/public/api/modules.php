<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'user_toggle') {
  require_role_api('user');
  $id = (string)($_POST['module_id'] ?? '');
  if ($id === '') { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Missing module_id']); exit; }

  if (!wnx_module_user_can_toggle($id)) {
    http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Module cannot be toggled']); exit;
  }

  $enabled = ($_POST['enabled'] ?? '') === '1';
  wnx_user_override_set($id, $enabled);
  echo json_encode(['ok'=>true,'module_id'=>$id,'enabled'=>wnx_module_enabled($id)]);
  exit;
}

if ($action === 'admin_set_policy') {
  require_role_api('admin');
  $id = (string)($_POST['module_id'] ?? '');
  $mode = (string)($_POST['mode'] ?? '');
  $allowed = ['default_on','default_off','forced_on','disabled_globally'];
  if ($id === '' || !in_array($mode, $allowed, true)) {
    http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Bad module_id or mode']); exit;
  }
  $policy = wnx_policy_load();
  if (!isset($policy['modules']) || !is_array($policy['modules'])) $policy['modules'] = [];
  $policy['modules'][$id] = ['mode' => $mode];
  $ok = wnx_policy_save($policy);
  echo json_encode(['ok'=>$ok]);
  exit;
}

http_response_code(400);
echo json_encode(['ok'=>false,'error'=>'Unsupported action']);
