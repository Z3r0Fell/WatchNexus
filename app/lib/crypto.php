<?php
declare(strict_types=1);

/**
 * Integration secrets encryption-at-rest (server-side only).
 * Uses libsodium secretbox with a 256-bit key (32 bytes).
 *
 * Store the key OUTSIDE the DB (env var WATCHNEXUS_SECRET_KEY_B64 or config).
 */

function crypto_key(): string {
  $b64 = WNX_SECRET_KEY_B64 ?: '';
  if ($b64 === '') return '';
  $key = base64_decode($b64, true);
  if ($key === false || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
    throw new RuntimeException('WATCHNEXUS_SECRET_KEY_B64 must decode to 32 bytes.');
  }
  return $key;
}

function encrypt_secret(string $plaintext): string {
  if ($plaintext === '') return '';
  $key = crypto_key();
  if ($key === '') {
    // Demo/dev convenience — still works, but clearly signals misconfig.
    return 'plain:' . base64_encode($plaintext);
  }
  $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
  $cipher = sodium_crypto_secretbox($plaintext, $nonce, $key);
  return 'v1:' . base64_encode($nonce) . ':' . base64_encode($cipher);
}

function decrypt_secret(string $packed): string {
  if ($packed === '' || $packed === null) return '';
  if (str_starts_with($packed, 'plain:')) {
    return base64_decode(substr($packed, 6), true) ?: '';
  }
  if (!str_starts_with($packed, 'v1:')) {
    throw new RuntimeException('Unknown secret format.');
  }
  [, $nonce_b64, $cipher_b64] = explode(':', $packed, 3);
  $nonce = base64_decode($nonce_b64, true);
  $cipher = base64_decode($cipher_b64, true);

  $key = crypto_key();
  if ($key === '') throw new RuntimeException('Missing encryption key.');
  $plain = sodium_crypto_secretbox_open($cipher, $nonce, $key);
  if ($plain === false) throw new RuntimeException('Decrypt failed (tampered or wrong key).');
  return $plain;
}
