<?php
declare(strict_types=1);

/**
 * Auth (DB-backed)
 * - Passwords are HASHED (Argon2id), not encrypted.
 * - Session-based login via $_SESSION['wnx_user_id'].
 */

function wnx_hash_password(string $pw): string {
  $hash = password_hash($pw, PASSWORD_ARGON2ID);
  if ($hash === false) {
    // fallback if Argon2id not available
    $hash = password_hash($pw, PASSWORD_BCRYPT);
  }
  if ($hash === false) throw new RuntimeException('Password hashing failed');
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

function current_user(): ?array {
  if (!isset($_SESSION['wnx_user_id'])) return null;
  $uid = (int)$_SESSION['wnx_user_id'];

  $pdo = db();
  $st = $pdo->prepare("SELECT id, email, display_name, status FROM users WHERE id = ? LIMIT 1");
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

function auth_register(string $email, string $password, string $displayName = ''): int {
  $email = strtolower(trim($email));
  $displayName = trim($displayName);

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new RuntimeException('Invalid email address.');
  }
  if ($displayName !== '' && strlen($displayName) > 120) {
    throw new RuntimeException('Display name too long.');
  }

  [$ok, $msg] = validate_user_password($password);
  if (!$ok) {
    throw new RuntimeException($msg);
  }

  $pdo = db();

  // Ensure not already registered
  $st = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
  $st->execute([$email]);
  if ($st->fetchColumn()) {
    throw new RuntimeException('Email already registered.');
  }

  $hash = password_hash($password, PASSWORD_ARGON2ID);

  $pdo->beginTransaction();
  try {
    $st = $pdo->prepare("INSERT INTO users (email, password_hash, display_name) VALUES (?, ?, ?)");
    $st->execute([$email, $hash, $displayName !== '' ? $displayName : null]);
    $uid = (int)$pdo->lastInsertId();

    // Default role: user (role_id = 1)
    $st = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, 1)");
    $st->execute([$uid]);

    $pdo->commit();
    return $uid;
  } catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
  }
}

function auth_login(string $email, string $password): int {
  $email = strtolower(trim($email));
  if ($email === '' || $password === '') throw new RuntimeException('Invalid credentials.');

  $pdo = db();
  $st = $pdo->prepare("SELECT id, password_hash, status FROM users WHERE email = ? LIMIT 1");
  $st->execute([$email]);
  $row = $st->fetch();

  if (!$row) throw new RuntimeException('Invalid credentials.');
  if ($row['status'] !== 'active') throw new RuntimeException('Account disabled.');

  if (!password_verify($password, $row['password_hash'])) {
    throw new RuntimeException('Invalid credentials.');
  }

  $uid = (int)$row['id'];
  $pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?")->execute([$uid]);
  return $uid;
}
