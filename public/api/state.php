<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$user = current_user();
$roles = function_exists('current_roles') ? current_roles() : [];

function module_enabled_safe(string $id): bool {
  return function_exists('wnx_module_enabled') ? (bool)wnx_module_enabled($id) : true;
}

$modules = [
  'calendar' => module_enabled_safe('calendar'),
  'browse'   => module_enabled_safe('browse'),
  'myshows'  => module_enabled_safe('myshows'),
  'settings' => module_enabled_safe('settings'),
  'themes'   => module_enabled_safe('themes'),
  'mod'      => module_enabled_safe('mod'),
  'admin'    => module_enabled_safe('admin'),
  'trakt'    => module_enabled_safe('trakt'),
  'seedr'    => module_enabled_safe('seedr'),
  'jackett'  => module_enabled_safe('jackett'),
  'prowlarr' => module_enabled_safe('prowlarr'),
];

echo json_encode([
  'ok' => true,
  'logged_in' => (bool)$user,
  'user' => $user ? [
    'id' => (int)$user['id'],
    'email' => (string)($user['email'] ?? ''),
    'display_name' => (string)($user['display_name'] ?? ''),
  ] : null,
  'roles' => array_values($roles),
  'modules' => $modules,
], JSON_PRETTY_PRINT);
