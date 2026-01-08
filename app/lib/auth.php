<?php
declare(strict_types=1);

/**
 * Auth (DB-backed)
 * - Passwords are HASHED (Argon2id/bcrypt), not encrypted.
 * - Session-based login via $_SESSION['wnx_user_id'].
 * - Login accepts either email OR username (display_name).
 * - Backward-compatible with older schemas (status vs is_disabled).
 */

function wnx_hash_password(string $pw): string {
  $hash = password_hash($pw, PASSWORD_ARGON2ID);
  if ($hash === false) {
    // fallback if Argon2id not available
    $hash = password_hash($pw, PASSWORD_BCRYPT);
  }
  if ($hash === false) {
    throw new RuntimeException('Password hashing failed');
  }
  return $hash;
}

function wnx_verify_password(string $pw, string $hash): bool {
  return password_verify($pw, $hash);
}

function validate_user_password(string $pw): array {
  $pw = trim($pw);

  // Passphrase mode: 20–64 chars, spaces allowed, 4+ words
  if (preg_match('/\s+/', $pw)) {
    if (strlen($pw) < 20 || strlen($pw) > 64) {
      return [false, 'Passphrase must be 20–64 characters.'];
    }
    $words = preg_split('/\s+/', $pw, -1, PREG_SPLIT_NO_EMPTY);
    if (count($words) < 4) {
      return [false, 'Passphrase must contain at least 4 words.'];
    }
    return [true, 'ok'];
  }

  // Password mode: 12–16 chars, no spaces, must include upper/lower/number/symbol
  if (strlen($pw) < 12 || strlen($pw) > 16) {
    return [false, 'Password must be 12–16 characters OR use a 20–64 char passphrase.'];
  }

  $ok = preg_match('/[A-Z]/', $pw)
     && preg_match('/[a-z]/', $pw)
     && preg_match('/[0-9]/', $pw)
     && preg_match('/[^A-Za-z0-9]/', $pw);

  if (!$ok) {
    return [false, 'Password must include upper/lower/number/symbol.'];
  }
  return [true, 'ok'];
}

function wnx_trunc(string $s, int $max): string {
  if ($max <= 0) return '';
  if (function_exists('mb_substr')) {
    return (string)mb_substr($s, 0, $max, 'UTF-8');
  }
  return (string)substr($s, 0, $max);
}

/**
 * Returns [$statusSelectExpr, $isActiveFn]
 * $statusSelectExpr must yield an alias named 'status'.
 */
function wnx_users_status_sql(): array {
  if (function_exists('wnx_db_has_column') && wnx_db_has_column('users', 'status')) {
    return ['status AS status', fn(array $row) => ((string)($row['status'] ?? 'active')) === 'active'];
  }
  if (function_exists('wnx_db_has_column') && wnx_db_has_column('users', 'is_disabled')) {
    return ["IF(is_disabled = 1, 'disabled', 'active') AS status", fn(array $row) => ((string)($row['status'] ?? 'active')) === 'active'];
  }
  return ["'active' AS status", fn(array $row) => true];
}

function current_user(): ?array {
  if (!isset($_SESSION['wnx_user_id'])) return null;
  $uid = (int)$_SESSION['wnx_user_id'];

  $pdo = db();
  [$statusSel, $_] = wnx_users_status_sql();

  $st = $pdo->prepare("SELECT id, email, display_name, $statusSel FROM users WHERE id = ? LIMIT 1");
  $st->execute([$uid]);
  $u = $st->fetch();
  return $u ?: null;
}

function auth_login_userid(int $userId): void {
  $_SESSION['wnx_user_id'] = $userId;
}

function auth_logout(): void {
  unset($_SESSION['wnx_user_id']);
}

function wnx_normalize_username(string $s): string {
  $s = trim($s);
  // collapse whitespace to single underscore (better for typing)
  $s = preg_replace('/\s+/', '_', $s) ?? $s;
  // strip weird characters but keep common username symbols
  $s = preg_replace('/[^A-Za-z0-9._-]/', '_', $s) ?? $s;
  $s = trim($s, '._-');
  if ($s === '') $s = 'user';
  return $s;
}

function wnx_suggest_username_from_email(string $email): string {
  $local = $email;
  $pos = strpos($email, '@');
  if ($pos !== false) $local = (string)substr($email, 0, $pos);
  $u = wnx_normalize_username($local);
  return wnx_trunc($u, 80);
}

function wnx_username_available(PDO $pdo, string $username): bool {
  $st = $pdo->prepare('SELECT id FROM users WHERE LOWER(display_name) = LOWER(?) LIMIT 1');
  $st->execute([$username]);
  return !$st->fetchColumn();
}

function wnx_claim_unique_username(PDO $pdo, string $desired, string $email): string {
  $base = trim($desired);
  if ($base === '') $base = wnx_suggest_username_from_email($email);
  $base = wnx_trunc(wnx_normalize_username($base), 80);

  // If it's free, take it
  if (wnx_username_available($pdo, $base)) return $base;

  // Otherwise, add a suffix
  $stem = wnx_trunc($base, 72); // keep room for _NN
  for ($i = 2; $i <= 99; $i++) {
    $cand = wnx_trunc($stem, 80 - (strlen((string)$i) + 1)) . '_' . $i;
    if (wnx_username_available($pdo, $cand)) return $cand;
  }

  // Last resort: add random
  $rand = (string)random_int(1000, 9999);
  $cand = wnx_trunc($stem, 80 - (strlen($rand) + 1)) . '_' . $rand;
  if (wnx_username_available($pdo, $cand)) return $cand;

  throw new RuntimeException('Unable to allocate a unique username.');
}

function auth_register(string $email, string $password, string $displayName = ''): int {
  $email = strtolower(trim($email));
  $displayName = trim($displayName);

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new RuntimeException('Invalid email address.');
  }

  [$ok, $msg] = validate_user_password($password);
  if (!$ok) {
    throw new RuntimeException($msg);
  }

  $pdo = db();

  // Ensure email not already registered
  $st = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
  $st->execute([$email]);
  if ($st->fetchColumn()) {
    throw new RuntimeException('Email already registered.');
  }

  // Ensure a usable username (display_name)
  $username = wnx_claim_unique_username($pdo, $displayName, $email);

  $hash = wnx_hash_password($password);

  $pdo->beginTransaction();
  try {
    // Always specify columns to match schema variations.
    $st = $pdo->prepare('INSERT INTO users (email, display_name, password_hash) VALUES (?, ?, ?)');
    $st->execute([$email, $username, $hash]);
    $uid = (int)$pdo->lastInsertId();

    // Default role: user (role_id = 1)
    $st = $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, 1)');
    $st->execute([$uid]);

    $pdo->commit();
    return $uid;
  } catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
  }
}

/**
 * Login with either email OR username.
 */
function auth_login_identifier(string $identifier, string $password): int {
  $identifier = trim($identifier);
  if ($identifier === '' || $password === '') {
    throw new RuntimeException('Invalid credentials.');
  }

  $pdo = db();
  [$statusSel, $isActive] = wnx_users_status_sql();

  $idLower = strtolower($identifier);
  $isEmail = (bool)filter_var($identifier, FILTER_VALIDATE_EMAIL);

  // Priority: email-first when it looks like an email, otherwise username-first.
  if ($isEmail) {
    $sql = "(SELECT id, password_hash, $statusSel FROM users WHERE LOWER(email) = ? LIMIT 1)
            UNION ALL
            (SELECT id, password_hash, $statusSel FROM users WHERE LOWER(display_name) = ? LIMIT 1)
            LIMIT 1";
  } else {
    $sql = "(SELECT id, password_hash, $statusSel FROM users WHERE LOWER(display_name) = ? LIMIT 1)
            UNION ALL
            (SELECT id, password_hash, $statusSel FROM users WHERE LOWER(email) = ? LIMIT 1)
            LIMIT 1";
  }

  $st = $pdo->prepare($sql);
  $st->execute([$idLower, $idLower]);
  $row = $st->fetch();

  if (!$row) {
    throw new RuntimeException('Invalid credentials.');
  }

  if (!$isActive($row)) {
    throw new RuntimeException('Account disabled.');
  }

  if (!wnx_verify_password($password, (string)$row['password_hash'])) {
    throw new RuntimeException('Invalid credentials.');
  }

  $uid = (int)$row['id'];

  // Best-effort update (column exists in all known schemas)
  try {
    $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$uid]);
  } catch (Throwable $e) {
    // ignore
  }

  return $uid;
}

/**
 * Back-compat: older callers still call auth_login(email, pw)
 */
function auth_login(string $email, string $password): int {
  return auth_login_identifier($email, $password);
}
