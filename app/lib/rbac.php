<?php
declare(strict_types=1);

function current_roles(): array {
  $u = current_user();
  if (!$u) return [];

  $pdo = db();
  $st = $pdo->prepare("
    SELECT r.name
    FROM user_roles ur
    JOIN roles r ON r.id = ur.role_id
    WHERE ur.user_id = ?
  ");
  $st->execute([(int)$u['id']]);
  return $st->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

function has_role(string $role): bool {
  $roles = current_roles();

  // Admin is supreme
  if (in_array('admin', $roles, true)) return true;

  if ($role === 'admin') return false;
  if ($role === 'mod') return in_array('mod', $roles, true);
  if ($role === 'user') return in_array('user', $roles, true) || in_array('mod', $roles, true);

  return false;
}

function require_role_html(string $role): void {
  if (!has_role($role)) {
    $roleName = ucfirst($role);
    echo '<div class="banner"><div class="badge">Access Denied</div><div><p>' . $roleName . ' access required.</p></div></div>';
    exit;
  }
}
