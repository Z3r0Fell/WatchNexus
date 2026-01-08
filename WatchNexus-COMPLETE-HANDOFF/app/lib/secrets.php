<?php
declare(strict_types=1);

/**
 * AES-256-GCM encryption for secrets-at-rest.
 * Key source: config()['secret_key_b64'] must decode to 32 bytes.
 */

function wnx_secret_key_raw(): string {
  $cfg = config();
  $b64 = $cfg['secret_key_b64'] ?? '';
  $raw = base64_decode($b64, true);
  if ($raw === false || strlen($raw) !== 32) {
    throw new RuntimeException('secret_key_b64 must be base64 of 32 random bytes');
  }
  return $raw;
}

/** @return array{cipher:string, nonce:string, tag:string} */
function wnx_encrypt_secret(array $secret): array {
  $key = wnx_secret_key_raw();
  $nonce = random_bytes(12); // GCM standard nonce size
  $tag = '';

  $plain = json_encode($secret, JSON_UNESCAPED_SLASHES);
  if ($plain === false) throw new RuntimeException('Failed to encode secret JSON');

  $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);
  if ($cipher === false || $tag === '') throw new RuntimeException('Encryption failed');

  return ['cipher' => $cipher, 'nonce' => $nonce, 'tag' => $tag];
}

function wnx_decrypt_secret(?string $cipher, ?string $nonce, ?string $tag): array {
  if (!$cipher || !$nonce || !$tag) return [];
  $key = wnx_secret_key_raw();

  $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);
  if ($plain === false) return [];

  $decoded = json_decode($plain, true);
  return is_array($decoded) ? $decoded : [];
}
