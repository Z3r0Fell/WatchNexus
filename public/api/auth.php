<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

function wants_json(): bool {
  // Allow explicit ajax=1 OR header-based
  if (isset($_GET['ajax']) && $_GET['ajax'] === '1') return true;
  if (isset($_POST['ajax']) && $_POST['ajax'] === '1') return true;

  $hdr = $_SERVER['HTTP_X_WNX_AJAX'] ?? '';
  if ($hdr === '1') return true;

  $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
  return stripos($accept, 'application/json') !== false;
}

function json_ok(array $data = []): void {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array_merge(['ok' => true], $data));
  exit;
}

function json_fail(int $code, string $msg): void {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok' => false, 'error' => $msg]);
  exit;
}

function redirect_err(string $page, string $msg): void {
  header('Location: /?page=' . $page . '&err=' . urlencode($msg));
  exit;
}

function redirect_to(string $url): void {
  header('Location: ' . $url);
  exit;
}

$action = (string)($_POST['action'] ?? '');
if ($action === '') {
  if (wants_json()) json_fail(400, 'Missing action');
  http_response_code(400);
  echo 'Missing action';
  exit;
}

try {
  if ($action === 'register') {
    $email = (string)($_POST['email'] ?? '');
    $username = (string)($_POST['username'] ?? ($_POST['display_name'] ?? ''));
    $pw = (string)($_POST['password'] ?? '');

    $uid = auth_register($email, $pw, $username);
    auth_login_userid($uid);

    if (wants_json()) json_ok(['redirect' => '/?page=calendar']);
    redirect_to('/?page=calendar');
  }

  if ($action === 'login') {
    $identifier = (string)($_POST['identifier'] ?? ($_POST['email'] ?? ''));
    $pw = (string)($_POST['password'] ?? '');

    $uid = auth_login_identifier($identifier, $pw);
    auth_login_userid($uid);

    if (wants_json()) json_ok(['redirect' => '/?page=calendar']);
    redirect_to('/?page=calendar');
  }

  if ($action === 'logout') {
    auth_logout();

    if (wants_json()) json_ok();
    redirect_to('/?page=calendar');
  }

  if (wants_json()) json_fail(400, 'Unsupported action');
  http_response_code(400);
  echo 'Unsupported action';
  exit;

} catch (Throwable $e) {
  $msg = $e->getMessage();

  if (wants_json()) {
    // Don’t leak internals beyond validation messages
    json_fail(400, $msg !== '' ? $msg : 'Request failed');
  }

  // Form mode
  if ($action === 'register') redirect_err('register', $msg !== '' ? $msg : 'Registration failed');
  if ($action === 'login') redirect_err('login', $msg !== '' ? $msg : 'Login failed');

  redirect_err('login', 'Server error');
}
